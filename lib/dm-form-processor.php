<?php

class ClsAutoLeadFormProcessor
{

    /**
     * @access public
     */
    public $form_attributes;

    /**
     * @access public
     */
    public $hidden_inputs;

    /**
     * @access public
     */
    public $visible_inputs;

    /**
     * Process the form and squeeze all the juices out of it
     * @access public
     * @param string $html The html code to process
     */
    public function process_form( $html )
    {
        $dom = new DOMDocument();
        @$dom->loadHTML( $html );
        $dom->preserveWhiteSpace = FALSE;
        $dom->strictErrorChecking = FALSE;
        $xpath = new DOMXPath( $dom );
        $form = $xpath->query( '//form[@action and @method]' )->item( 0 );

        if ( $form->hasAttributes() )
        {
            foreach ( $form->attributes as $index => $attr )
            {
                $this->form_attributes[$attr->name] = $attr->value;
            }
        }

        $this->hidden_inputs = $xpath->query( '//input[@type="hidden"]' );
        $this->visible_inputs = $xpath->query( '//input[@type="text"]|input[@type="email"]' );
    }



    /**
     * Checks if the html contains a form inside it
     * @access public
     * @param string $html The html to check for
     * @return bool Returns TRUE if valid else returns FALSE
     */
    public function is_form_valid( $html )
    {
        $dom = new DOMDocument();
        @$dom->loadHTML( $html );
        $dom->preserveWhiteSpace = FALSE;
        $dom->strictErrorChecking = FALSE;
        $xpath = new DOMXPath( $dom );
        $form = $xpath->query( '//form' );
        return $form->length > 0 ? TRUE : FALSE;
    }



    /**
     * Get the plain form format
     * @access public
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function get_vanilla_form()
    {
        ob_start();
        echo '<form ' . $this->create_attributes( $this->form_attributes ) . '>';
        foreach ( $this->visible_inputs as $input )
        {
            if ( preg_match( '/(name)/is', ( string ) $input->getAttribute( 'name' ) ) )
            {
                if ( !$input->getAttribute( 'placeholder' ) )
                {
                    echo '<input type="' . $input->getAttribute( 'type' ) . '" placeholder="Enter your name" name="' . $input->getAttribute( 'name' ) . '" value="' . $input->getAttribute( 'value' ) . '" />' . "\n";
                }
                else
                {
                    echo '<input type="' . $input->getAttribute( 'type' ) . '" name="' . $input->getAttribute( 'name' ) . '" value="' . $input->getAttribute( 'value' ) . '" />' . "\n";
                }
            }

            if ( preg_match( '/(email)/is', ( string ) $input->getAttribute( 'name' ) ) )
            {
                if ( !$input->getAttribute( 'placeholder' ) )
                {
                    echo '<input type="' . $input->getAttribute( 'type' ) . '" placeholder="Enter your email" name="' . $input->getAttribute( 'name' ) . '" value="' . $input->getAttribute( 'value' ) . '" />' . "\n";
                }
                else
                {
                    echo '<input type="' . $input->getAttribute( 'type' ) . '" name="' . $input->getAttribute( 'name' ) . '" value="' . $input->getAttribute( 'value' ) . '" />' . "\n";
                }
            }
        }

        foreach ( $this->hidden_inputs as $input )
        {
            echo '<input type="' . $input->getAttribute( 'type' ) . '" name="' . $input->getAttribute( 'name' ) . '" value="' . $input->getAttribute( 'value' ) . '" />' . "\n";
        }
        echo '</form>';
        $form = ob_get_contents();
        ob_end_clean();
        return $form;
    }



    /**
     * create the attributes and value string
     * @access public
     * @param $atts Mixed The array of attributes and values
     * @return string The string of attributes and values
     * @author Anver Sadutt <anvergdr@gmail.com>
     */
    public function create_attributes( $atts )
    {
        foreach ( $atts as $key => $value )
        {
            $string .= $key . '="' . $value . '" ';
        }
        return $string;
    }



    /**
     * change the value of an element
     * @access public
     * @param string $html The html string 
     * @param string $element The element to change value
     * @param string $element Decides which attribute of the element
     * @return string The processed html
     */
    public function set_attribute( $html, $query, $attr, $value )
    {
        try
        {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = FALSE;
            $dom->strictErrorChecking = FALSE;
            $dom->formatOutput = FALSE;
            @$dom->loadHTML( $html );
            //$xml = new DOMXPath( $dom );
            //$result = $xml->query( $query );
            //$result = new SimpleXMLElement( $data, $options, $data_is_url, $ns, $is_prefix );
            $xml = @simplexml_import_dom( $dom );
            $result = $xml->xpath( $query );
            isset( $result[0]->attributes()->$attr ) ? $result[0]->attributes()->$attr = $value : $result[0]->addAttribute( $attr, $value );
            $dom->removeChild( $dom->firstChild );
            $dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );
            return $dom->saveHTML();
        }
        catch ( Exception $e )
        {
            return FALSE;
        }
    }



    /**
     * Creates a new element
     * @access public
     * @param string $html The html string
     * @param string $ele The new element to create
     * @param string $atts The attributes of the new element
     * @return string The processed new html string
     */
    public function create_element( $html, $ele, $atts, $query )
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = FALSE;
        $dom->strictErrorChecking = FALSE;
        $dom->formatOutput = FALSE;
        @$dom->loadHTML( $html );
        $xml = @simplexml_import_dom( $dom );
        $append_element = $xml->xpath( $query );
        $new_element = $append_element[0];
        $new_element = $append_element[0]->addChild( $ele );
        foreach ( $atts as $attr_name => $attr_value )
        {
            $new_element->addAttribute( $attr_name, $attr_value );
        }
        $dom->removeChild( $dom->firstChild );
        $dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );
        return $dom->saveHTML();
    }



}