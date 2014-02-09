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

          $values[$name] = self::askAndValidate($task, strip_tags($field->renderLabel()) . $default . ":", $form->getValidator($name), array('value'=>$form->getDefault($name)));

          break;
        case self::TYPE_DEFAULT:
        default:
          $default = '';

          if (strlen($form->getDefault($name)) > 0) {
            $default = " [" . $form->getDefault($name) . "]";
          }

          $values[$name]  = self::askAndValidate($task, strip_tags($field->renderLabel()) . $default . ":", $form->getValidator($name), array('value'=>$form->getDefault($name)));

          break;
      }
    }

    $form->bind($values);
    return $form;
  }

  /**
   * @param $task
   * @param $question
   * @param sfValidatorBase $validator
   * @param array $options
   * @return mixed
   * @throws Exception
   * @throws null
   * @throws sfValidatorError
   */
  private static function askAndValidate($task, $question, sfValidatorBase $validator, array $options = array())
  {
    if (!is_array($question))
    {
      $question = array($question);
    }

    $options = array_merge(array(
      'value'    => null,
      'attempts' => false,
      'style'    => 'QUESTION',
    ), $options);


    // no, ask the user for a valid user
    $error = null;
    while (false === $options['attempts'] || $options['attempts']--)
    {
      if (null !== $error)
      {
        $task->logBlock($error->getMessage(), 'ERROR');
      }

      $value = self::ask($task, $question, $options['style'], $options['value']);

      try
      {
        return $validator->clean($value);
      }
      catch (sfValidatorError $error)
      {
      }
    }

    throw $error;
  }

  /**
   * Asks a question to the user.
   *
   * @param string|array $question The question to ask
   * @param string       $style    The style to use (QUESTION by default)
   * @param string       $default  The default answer if none is given by the user
   *
   * @param string       The user answer
   */
  private static function ask($task, $question, $style = 'QUESTION', $default = null)
  {
    if (false === $style)
    {
      $task->log($question);
    }
    else
    {
      $task->logBlock($question, null === $style ? 'QUESTION' : $style);
    }

    $ret = fgets(STDIN);
    if($ret == PHP_EOL)
    {
      return $default;
    }

    $ret = str_replace(PHP_EOL, '', $ret);

    return $ret;
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