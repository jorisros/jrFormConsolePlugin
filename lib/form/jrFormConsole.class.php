<?php

class jrFormConsole
{
  const COMMANDLINE_STYLE = 'QUESTION';
  const TYPE_DEFAULT = 'text';
  const TYPE_SELECT = 'select';

  /**
   * Converts a form into a interactive console form
   *
   * @param sfBaseTask $task
   * @param sfFormSymfony $form
   * @return sfFormSymfony
   */
  static public function connectTaskForm(sfBaseTask $task, sfFormSymfony $form)
  {
    $form->disableCSRFProtection();

    $task->logBlock(get_class($form), self::COMMANDLINE_STYLE);
    $task->log(' ');

    $fields = $form->getFormFieldSchema();
    $values = array();
    foreach ($fields as $name => $field) {
      $widget = $field->getWidget();

      $type = self::getType($widget);

      switch ($type) {
        case self::TYPE_SELECT:

          $choices = $widget->getOption('choices');

          foreach($choices as $value => $label)
          {
            $task->logBlock("[".$value."] ".$label, self::COMMANDLINE_STYLE);
          }
          $default = '';

          if (strlen($form->getDefault($name)) > 0) {
            $default = " [" . $form->getDefault($name) . "]";
          }

          $values[$name] = $task->askAndValidate(strip_tags($field->renderLabel()) . $default . ":", $form->getValidator($name), array());

          break;
        case self::TYPE_DEFAULT:
        default:
          $default = '';

          if (strlen($form->getDefault($name)) > 0) {
            $default = " [" . $form->getDefault($name) . "]";
          }

          $values[$name] = $task->askAndValidate(strip_tags($field->renderLabel()) . $default . ":", $form->getValidator($name), array());

          break;
      }
    }

    $form->bind($values);
    return $form;
  }

  /**
   * Shows the filled in form and ask for confirmation
   *
   * @param sfBaseTask $task
   * @param sfFormSymfony $form
   * @return bool
   */
  public static function confirm(sfBaseTask $task, sfFormSymfony $form)
  {
    $fields = $form->getFormFieldSchema();
    $values = array();
    $task->log(' ');
    foreach ($fields as $name => $field) {
      /** @var sfWidgetForm $widget */
      $widget = $field->getWidget();

      $type = self::getType($widget);

      switch ($type) {
        case self::TYPE_SELECT:
          $choices = $widget->getOption('choices');
          $task->log(strip_tags($field->renderLabel()).": ".$choices[$form->getValue($name)]);
          break;
        case self::TYPE_DEFAULT:
        default:
          $task->log(strip_tags($field->renderLabel()).": ".$form->getValue($name));
          break;
      }
    }

    $question = $task->askConfirmation('Are you sure to commit [Y]', self::COMMANDLINE_STYLE, true);

    if(strtolower($question) == 'y')
    {
      return true;
    }

    return false;
  }
  
  /**
   * Returns the type of the question
   *
   * @param sfWidgetForm $widget
   * @return string
   */
  private static function getType(sfWidgetForm $widget)
  {
    $type = self::TYPE_DEFAULT;

    if (is_array($widget->getOption('choices')) && count($widget->getOption('choices') > 0)) {
      $type = self::TYPE_SELECT;
    }

    return $type;
  }
}