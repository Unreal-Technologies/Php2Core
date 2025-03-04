<?php
namespace Php2Core\NoHTML\Materialize;

class Form
{
    /**
     * @var \Php2Core\NoHTML\Xhtml
     */
    private \Php2Core\NoHTML\Xhtml $oForm;
    
    /**
     * @param \Php2Core\NoHTML\Xhtml $container
     * @param Form\Methods $method
     * @param \Closure $optionsCb
     */
    public function __construct(\Php2Core\NoHTML\Xhtml $container, Form\Methods $method, \Closure $optionsCb = null)
    {
        $options = Form\Options::Default();
        if($optionsCb !== null)
        {
            $optionsCb($options);
        }
        
        $size = $options -> size();
        $offset = $options -> offset();
        $id = 'frm'.date('YmdHis').rand(0, 9999);
        
        
        $reference = null;
        $container -> add('div@.row/form@#'.$id.'&.col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value).'&method='.$method -> value.'&onsubmit=return Form.Validate(this);/div@.container/div@.row', function(\Php2Core\NoHTML\Xhtml $form) use(&$reference)
        {
            $reference = $form;
        });
        
        $this -> oForm = $reference;
        
        
        $containerParent = $reference -> parent();
        $containerParent -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
        {
            $script -> attributes() -> set('type', 'text/javascript');
        }) -> text('$(document).ready(function()'
            . '{'
                . '$(\'select\').formSelect();'
                . 'M.updateTextFields();'
                . 'Form.initialize(document.getElementById(\''.$id.'\'));'
            . '});'
        );
    }
    
    /**
     * @return \Php2Core\NoHTML\Xhtml
     */
    public function reference(): \Php2Core\NoHTML\Xhtml
    {
        return $this -> oForm;
    }
    
    /**
     * @param string $id
     * @param string $text
     * @param Form\InputTypes $type
     * @param mixed $value
     * @param bool $required
     * @param \Closure $optionsCb
     * @return \Php2Core\NoHTML\Xhtml
     * @throws \Php2Core\Exceptions\NotImplementedException
     */
    public function field(string $id, string $text, Form\InputTypes $type, mixed $value, bool $required, \Closure $optionsCb = null): \Php2Core\NoHTML\Xhtml
    {
        $options = Form\Options::Default();
        if($optionsCb !== null)
        {
            $optionsCb($options);
        }
        
        $inputType = null;
        switch($type)
        {
            default:
                $inputType = $type -> value;
                break;
        }
        
        if($type === Form\InputTypes::YesNo)
        {
            if(!is_bool($value))
            {
                throw new \Php2Core\Exceptions\NotImplementedException('Value YesNo not boolean');
            }
            
            return $this -> select($options, $text, $id, $value, $required, [
                ['text' => 'Yes', 'value' => 1, 'selected' => $value === true],
                ['text' => 'No', 'value' => 0, 'selected' => $value === false],
            ]);
        }
        else
        {
            return $this -> input($options, $text, $id, $inputType, $required, $value);
        }
    }
    
    /**
     * @param Form\Options $options
     * @param string $text
     * @param string $id
     * @param mixed $value
     * @param bool $required
     * @param array $list
     * @return \Php2Core\NoHTML\Xhtml|null
     */
    private function select(Form\Options $options, string $text, string $id, mixed $value, bool $required, array $list): ?\Php2Core\NoHTML\Xhtml
    {
        $size = $options -> size();
        $offset = $options -> offset();
        $input = null;
        
        $this -> oForm -> add('div@.input-field col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $field) use($text, $id, &$input, $value, $list, $required)
        {
            $input = $field -> add('select@name='.$id.'&.validate&#'.$id);
            $input -> add('option@disabled=disabled@selected=selected') -> text('Choose your option');
            
            $hasSelected = false;
            foreach($list as $item)
            {
                if($item['selected'])
                {
                    $hasSelected = true;
                }
                $input -> add('option@value='.$item['value'].($item['selected'] ? '&selected=selected' : null)) -> text($item['text']);
            }
            
            $attributes = $input -> attributes();
            $clsPrefix = '';
            if(!$hasSelected && $required)
            {
                $clsPrefix = 'in';
            }
            $attributes -> set('class', $attributes -> get('class').' '.$clsPrefix.'valid');

            $field -> add('label@for='.$id) -> text($text.' <span class="required">'.($required ? '*' : '&nbsp;').'</span>');
            $field -> add('span@.helper-text&for='.$id);
        });
        
        $attributes = $input -> attributes();
        
        if($required)
        {
            $attributes -> set('required', 'required');
        }
        
        return $input;
    }
    
    /**
     * @param Form\Options $options
     * @param string $text
     * @param string $id
     * @param string $inputType
     * @param bool $required
     * @param mixed $value
     * @return \Php2Core\NoHTML\Xhtml|null
     */
    private function input(Form\Options $options, string $text, string $id, string $inputType, bool $required, mixed $value): ?\Php2Core\NoHTML\Xhtml
    {
        $size = $options -> size();
        $offset = $options -> offset();
        $input = null;
        
        $this -> oForm -> add('div@.input-field col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $field) use($text, $id, $inputType, &$input, $value, $required)
        {
            $input = $field -> add('input@placeholder='.$text.'&name='.$id.'&type='.$inputType.'&.validate&#'.$id);
            $input -> attributes() -> set('value', $value);
            $field -> add('label@for='.$id.'&.active') -> text($text.' <span class="required">'.($required ? '*' : '&nbsp;').'</span>');
            $field -> add('span@.helper-text&for='.$id);
            
            $attributes = $input -> attributes();
            $clsPrefix = '';
            if(($value === null || $value == '') && $required)
            {
                $clsPrefix = 'in';
            }
            $attributes -> set('class', $attributes -> get('class').' '.$clsPrefix.'valid');
        });

        $attributes = $input -> attributes();
        
        if($required)
        {
            $attributes -> set('required', 'required');
        }

        $min = $options -> min();
        if($min !== null)
        {
            $attributes -> set('min', $min);
        }

        $max = $options -> max();
        if($max !== null)
        {
            $attributes -> set('max', $max);
        }

        $step = $options -> step();
        if($step !== null)
        {
            $attributes -> set('step', $step);
        }
        
        return $input;
    }
    
    /**
     * @param string $text
     * @param \Closure $optionsCb
     * @return \Php2Core\NoHTML\Xhtml
     * @throws \Php2Core\Exceptions\NotImplementedException
     */
    public function submit(string $text, \Closure $optionsCb = null): \Php2Core\NoHTML\Xhtml
    {
        $id = $this -> oForm -> parent() -> parent() -> attributes() -> get('id');
        if($id === null)
        {
            throw new \Php2Core\Exceptions\NotImplementedException('No form ID found.');
        }
        
        $button = $this -> button($text, 'submit()', $optionsCb);
        $this -> oForm -> parent() -> add('script', function(\Php2Core\NoHTML\Xhtml $js)
        {
            $js -> attributes() -> set('type', 'text/javascript');
        }) -> text('function submit()'
            . '{'
                . 'let form = document.getElementById("'.$id.'");'
                . 'if(Form.validate(form))'
                . '{'
                    . 'form.submit();'
                . '}'
            . '}'
        );
        
        return $button;
    }
    
    /**
     * @param string $text
     * @param string $action
     * @param \Closure $optionsCb
     * @return \Php2Core\NoHTML\Xhtml|null
     */
    public function button(string $text, string $action, \Closure $optionsCb = null): ?\Php2Core\NoHTML\Xhtml
    {
        $options = Form\Options::Default();
        if($optionsCb !== null)
        {
            $optionsCb($options);
        }
        
        $size = $options -> size();
        $offset = $options -> offset();
        
        $object = null;
        $this -> oForm -> add('div@.col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $button) use($text, $action, &$object)
        {
            $object = $button -> add('a@.waves-effect waves-light btn&onclick='.$action.';');
            $object -> text($text);
        });

        return $object;
    }
}