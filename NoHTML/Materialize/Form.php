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
     * @param Columns $size
     * @param Columns|null $offset
     */
    public function __construct(\Php2Core\NoHTML\Xhtml $container, Form\Methods $method, Columns $size = Columns::S12, ?Columns $offset = null)
    {
        $reference = null;
        $container -> add('div@.row/form@.col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value).'&method='.$method -> value.'/div@.container/div@.row', function(\Php2Core\NoHTML\Xhtml $form) use(&$reference)
        {
            $reference = $form;
        });
        
        $this -> oForm = $reference;
    }
    
    /**
     * @param string $id
     * @param string $text
     * @param Form\InputTypes $type
     * @param Columns $size
     * @param Columns|null $offset
     * @return \Php2Core\NoHTML\Xhtml
     */
    public function field(string $id, string $text, Form\InputTypes $type, Columns $size = Columns::S12, ?Columns $offset = null): \Php2Core\NoHTML\Xhtml
    {
        $input = null;
        $this -> oForm -> add('div@.input-field col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $field) use($text, $id, $type, &$input)
        {
            $input = $field -> add('input@placeholder='.$text.'&name='.$id.'&type='.$type -> value.'&.validate&#'.$id);
            $field -> add('label@for='.$id) -> text($text);
        });
        
        return $input;
    }
    
    /**
     * @param string $text
     * @param string $action
     * @param Columns $size
     * @param Columns|null $offset
     * @return void
     */
    public function button(string $text, string $action, Columns $size = Columns::S12, ?Columns $offset = null): void
    {
        $this -> oForm -> add('div@.col '.$size -> value.($offset === null ? '' : '  offset-'.$offset -> value), function(\Php2Core\NoHTML\Xhtml $button) use($text, $action)
        {
            $button -> add('a@.waves-effect waves-light btn&onclick='.$action.';') -> text($text);
        });
    }
}