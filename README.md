jrFormConsolePlugin
===================

## Installation

### The Git way
Add the plugin as submodule to the project
```
git submodule add git@github.com:jorisros/jrFormConsolePlugin.git plugins/jrFormConsolePlugin
git submodule update --init --recursive
```

Add the plugin to the project in config/ProjectConfiguration.class.php

``` php
// config/ProjectConfiguration.class.php

class ProjectConfiguration extends sfProjectConfiguration
{
    public function setup()
    {
        $this->enablePlugins(array(
            'jrFormConsolePlugin',
            ...
        ));
    }
}
```

## Usage

Open a form, and add use the static function 'connectTaskForm' to convert the form into a console form.

``` php
  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    $form = new ContactForm();

    $form = jrFormConsole::connectTaskForm($this, $form);

    if($form->isValid())
    {
      if(jrFormConsole::confirm($this, $form))
      {
        //save data
      }
    }
  }
```
