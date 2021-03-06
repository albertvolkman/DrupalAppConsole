<?php
namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\GeneratorCommand;
use Drupal\AppConsole\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Generator\FormGenerator;
use Drupal\AppConsole\Command\Validators;

class GeneratorFormCommand extends GeneratorCommand {

  protected function configure() {

    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('name','',InputOption::VALUE_OPTIONAL, 'Form name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
      ))
      ->setDescription('Generate form')
      ->setHelp('The <info>generate:form</info> command helps you generate a new form.')
      ->setName('generate:form');
  }

  /**
   * Execute method
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $services = $input->getOption('services');
    $update_routing = $input->getOption('routing');
    $name = $input->getOption('name');

    $map_service = array();
    foreach ($services as $service) {
      $class = get_class($this->getContainer()->get($service));
      $map_service[$service] = array(
        'name'  => $service,
        'machine_name' => str_replace('.', '_', $service),
        'class' => $class,
        'short' => end(explode('\\',$class))
      );
    }

    $generator = $this->getGenerator();
    $generator->generate($module, $name, $controller, $map_service);

    if ($update_routing) {
      echo "update";
    }

    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * [interact description]
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal form generator');

    $d = $this->getHelperSet()->get('dialog');

    // Module name
    $modules = $this->getModules();
    $module = $d->askAndValidate(
      $output,
      $dialog->getQuestion('Enter your module: '),
      function($module) use ($modules){
        return Validators::validateModuleExist($module, $modules);
      },
      false,
      '',
      $modules
    );
    $input->setOption('module', $module);

    // Module name
    $name = $this->getName();
    $name = $dialog->ask($output, $dialog->getQuestion('Enter the form name', 'DefaultForm'), 'DefaultForm');
    $input->setOption('name', $name);

    // Services
    $service_collection = array();
    $services = $this->getServices();
    while(true){
      $service = $d->askAndValidate(
        $output,
        $dialog->getQuestion('Enter your service: '),
        function($service) use ($services){
          return Validators::validateServiceExist($service, $services);
        },
        false,
        null,
        $services
      );

      if ($service == null) {
        break;
      }
      array_push($service_collection, $service);
      $service_key = array_search($service, $services, true);
      if ($service_key >= 0)
        unset($services[$service_key]);
    }
    $input->setOption('services', $service_collection);

    // Routing
    /**
     * Generate routing
     * @var [type]
     */
    $routing = $input->getOption('routing');
    if (!$routing && $dialog->askConfirmation($output, $dialog->getQuestion('Update routing file?', 'yes', '?'), true)) {
        $routing = true;
    }
    $input->setOption('routing', $routing);

  }

  /**
    * Get a filesystem
    * @return [type] Drupal Filesystem
    */
  protected function createGenerator() {
    return new FormGenerator();
  }

}

