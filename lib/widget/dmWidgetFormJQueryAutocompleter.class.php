<?php
class dmWidgetFormJQueryAutocompleter extends sfWidgetForm
{
	/**
	 * Configures the current widget.
	 *
	 * @param array $options     An array of options
	 * @param array $attributes  An array of default HTML attributes
	 *
	 * @see sfWidgetForm
	 */
	protected function configure($options = array(), $attributes = array())
	{
    
    $this->addRequiredOption('url');
    $this->addOption('value_callback');
    $this->addOption('widget_type', 'input');
    $this->addOption('config', '{ }');

    // this is required as it can be used as a renderer class for sfWidgetFormChoice
    $this->addOption('choices');
    
		$this->addOption('dispatcher');
		$this->addOption('response');
		$this->addOption('request');

		parent::configure($options, $attributes);
		
		$this->addOption('config', array('do_not_autocomplete' => true));
    
	}


	public function render($name, $value = null, $attributes = array(), $errors = array())
	{
    // set multiple = true when textarea widget_type set
    if ($this->getOption('widget_type') == 'textarea') {
      if (!(array_key_exists('multiple', $this->getOption('config', array())))) {
        $this->setOption('config', array_merge($this->getOption('config', array()), array('multiple' => true)));
      }
    }
    
		$visibleValue = $this->getOption('value_callback') ? call_user_func($this->getOption('value_callback'), $value) : $value;

		if(!dm::isCli())
		{
			$this->name = $name;
			$this->value = $value;
			$this->attributes = $attributes;
			$this->errors = $errors;

			$dispatcher = $this->getOption('dispatcher');
			if(!$dispatcher)
			{
				$dispatcher = dmContext::getInstance()->getEventDispatcher();
			}
			$dispatcher->connect('layout.filter_config', array($this, 'listenToLayoutFilterConfigEvent'));
		}


		$request = $this->getOption('request');
		if(!$request) $request = dmContext::getInstance()->getRequest();
		$ajax = $request->isXmlHttpRequest();

		if($ajax)
		{
			$config =  '<script type="text/javascript"> dm_configuration = $.extend(dm_configuration, ' . json_encode($this->getJavascriptConfig()) . ');</script>';
		}

		$html = $this->renderTag('input', array('type' => 'hidden', 'name' => $name, 'value' => $value));
    
    if ($this->getOption('widget_type') == 'textarea') {
		  $html .= $this->renderContentTag('textarea', $visibleValue, array_merge(array('type' => $this->getOption('type'), 'name' => 'autocomplete_' . $name, /*'value' => $visibleValue*/), $attributes));
    } else {
      $html .= $this->renderTag('input', array_merge(array('type' => $this->getOption('type'), 'name' => 'autocomplete_' . $name, 'value' => $visibleValue), $attributes));
    }
		$html .= ($ajax ? $config : '');

    return $html;

	}

	public function getJavaScripts()
	{
		return array(
			'/sfFormExtraPlugin/js/jquery.autocompleter.js', 
			'/dmFormExtraPlugin/js/dmFormAutocomplete.js'
		);
	}

	public function getStylesheets()
	{
		return array('/sfFormExtraPlugin/css/jquery.autocompleter.css' => 'all');
	}

	public function getJavascriptConfig()
	{
		return $this->listenToLayoutFilterConfigEvent(null, array());
	}


	public function listenToLayoutFilterConfigEvent($event, $value)
	{
		if(!isset($value['autocomplete']))
		{
			$value['autocomplete'] = array();
		}

		$value['autocomplete'][$this->generateId('autocomplete_'.$this->name)] = array(
			'id' => $this->generateId('autocomplete_'.$this->name),
			'url' => $this->getOption('url'),
			'config' => $this->getOption('config'),
			'input' => $this->generateId($this->name),
		);

		return $value;
	}
}