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
        $container -> add('div@.row/form@#'.$id.'&.col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value).'&method='.$method -> value.'/div@.container/div@.row', function(\Php2Core\NoHTML\Xhtml $form) use(&$reference)
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
            . '});'
        );
    }
    
    /**
     * @param string $id
     * @param string $text
     * @param Form\InputTypes $type
     * @param mixed $value
     * @param \Closure $optionsCb
     * @return \Php2Core\NoHTML\Xhtml
     * @throws \Php2Core\Exceptions\NotImplementedException
     */
    public function field(string $id, string $text, Form\InputTypes $type, mixed $value, \Closure $optionsCb = null): \Php2Core\NoHTML\Xhtml
    {
        $options = Form\Options::Default();
        if($optionsCb !== null)
        {
            $optionsCb($options);
        }
        
        $size = $options -> size();
        $offset = $options -> offset();
        
        $inputType = null;
        switch($type)
        {
            default:
                $inputType = $type -> value;
                break;
        }
        
        $input = null;
        if($type === Form\InputTypes::YesNo)
        {
            if(!is_bool($value))
            {
                throw new \Php2Core\Exceptions\NotImplementedException('Value YesNo not boolean');
            }
            
            $this -> oForm -> add('div@.input-field col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $field) use($text, $id, $inputType, &$input, $value)
            {
                $input = $field -> add('select@name='.$id.'&.validate&#'.$id);
                $input -> add('option@disabled=disabled@selected=selected') -> text('Choose your option');
                $input -> add('option@value=1'.($value === true ? '&selected=selected' : null)) -> text('Yes');
                $input -> add('option@value=0'.($value === false ? '&selected=selected' : null)) -> text('No');
                $field -> add('label@for='.$id) -> text($text);
            });
        }
        else
        {
            $this -> oForm -> add('div@.input-field col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $field) use($text, $id, $inputType, &$input, $value)
            {
                $input = $field -> add('input@placeholder='.$text.'&name='.$id.'&type='.$inputType.'&.validate&#'.$id);
                $input -> attributes() -> set('value', $value);
                $field -> add('label@for='.$id) -> text($text);
            });
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
                . 'document.getElementById("'.$id.'").submit();'
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