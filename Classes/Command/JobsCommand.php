<?php

/**
 * @license GPLv3, http://www.gnu.org/copyleft/gpl.html
 * @copyright Aimeos (aimeos.org), 2018
 * @package TYPO3
 */


namespace Aimeos\Aimeos\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class JobsCommand extends Command
{
	protected static $defaultName = 'aimeos:jobs';


	/**
	 * Configures the command name and description.
	 */
	protected function configure()
	{
		$names = '';
		$aimeos = new \Aimeos\Bootstrap( [] );
		$cntlPaths = $aimeos->getCustomPaths( 'controller/jobs' );
		$controllers = \Aimeos\Controller\Jobs\Factory::getControllers( $this->getBareContext(), $aimeos, $cntlPaths );

		foreach( $controllers as $key => $controller ) {
			$names .= str_pad( $key, 30 ) . $controller->getName() . PHP_EOL;
		}

		$this->setName( self::$defaultName );
		$this->setDescription( 'Executes the job controllers' );
		$this->addArgument( 'jobs', InputArgument::REQUIRED, 'One or more job controller names like "admin/job customer/email/watch"' );
		$this->addArgument( 'site', InputArgument::OPTIONAL, 'Site codes to execute the jobs for like "default unittest" (none for all)' );
		$this->setHelp( "Available jobs are:\n" . $names );
	}


	/**
	 * Executes the job controllers.
	 *
	 * @param InputInterface $input Input object
	 * @param OutputInterface $output Output object
	 */
	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$context = $this->getContext();
		$process = $context->getProcess();
		$aimeos = $this->getContainer()->get( 'aimeos' )->get();

		$jobs = explode( ' ', $input->getArgument( 'jobs' ) );
		$localeManager = \Aimeos\MShop\Locale\Manager\Factory::createManager( $context );

		foreach( $this->getSiteItems( $context, $input ) as $siteItem )
		{
			$localeItem = $localeManager->bootstrap( $siteItem->getCode(), '', '', false );
			$localeItem->setLanguageId( null );
			$localeItem->setCurrencyId( null );

			$context->setLocale( $localeItem );

			$output->writeln( sprintf( 'Executing the Aimeos jobs for "<info>%s</info>"', $siteItem->getCode() ) );

			foreach( $jobs as $jobname )
			{
				$fcn = function( $context, $aimeos, $jobname ) {
					\Aimeos\Controller\Jobs\Factory::createController( $context, $aimeos, $jobname )->run();
				};

				$process->start( $fcn, [$context, $aimeos, $jobname], true );
			}
		}

		$process->wait();
	}


	/**
	 * Returns a bare context object
	 *
	 * @return \Aimeos\MShop\Context\Item\Standard Context object containing only the most necessary dependencies
	 */
	protected function getBareContext()
	{
		$ctx = new \Aimeos\MShop\Context\Item\Standard();

		$conf = new \Aimeos\MW\Config\PHPArray( array(), array() );
		$ctx->setConfig( $conf );

		$locale = \Aimeos\MShop\Factory::createManager( $ctx, 'locale' )->createItem();
		$locale->setLanguageId( 'en' );
		$ctx->setLocale( $locale );

		$i18n = new \Aimeos\MW\Translation\None( 'en' );
		$ctx->setI18n( array( 'en' => $i18n ) );

		return $ctx;
	}


	/**
	 * Returns a context object
	 *
	 * @return \Aimeos\MShop\Context\Item\Standard Context object
	 */
	protected function getContext()
	{
		$container = $this->getContainer();
		$aimeos = $container->get('aimeos')->get();
		$context = $container->get( 'aimeos_context' )->get( false, 'command' );

		$tmplPaths = $aimeos->getCustomPaths( 'controller/jobs/templates' );
		$tmplPaths = array_merge( $tmplPaths, $aimeos->getCustomPaths( 'client/html/templates' ) );
		$view = $container->get('aimeos_view')->create( $context, $tmplPaths );

		$langManager = \Aimeos\MShop\Locale\Manager\Factory::createManager( $context )->getSubManager( 'language' );
		$langids = array_keys( $langManager->searchItems( $langManager->createSearch( true ) ) );
		$i18n = $this->getContainer()->get( 'aimeos_i18n' )->get( $langids );

		$context->setEditor( 'aimeos:jobs' );
		$context->setView( $view );
		$context->setI18n( $i18n );

		return $context;
    }


	/**
	 * Returns the enabled site items which may be limited by the input arguments.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context item object
	 * @param InputInterface $input Input object
	 * @return \Aimeos\MShop\Locale\Item\Site\Interface[] List of site items
	 */
	protected function getSiteItems( \Aimeos\MShop\Context\Item\Iface $context, InputInterface $input )
	{
		$manager = \Aimeos\MShop\Locale\Manager\Factory::createManager( $context )->getSubManager( 'site' );
		$search = $manager->createSearch();

		if( ( $codes = (string) $input->getArgument( 'site' ) ) !== '' ) {
			$search->setConditions( $search->compare( '==', 'locale.site.code', explode( ' ', $codes ) ) );
		}

		return $manager->searchItems( $search );
	}
}
