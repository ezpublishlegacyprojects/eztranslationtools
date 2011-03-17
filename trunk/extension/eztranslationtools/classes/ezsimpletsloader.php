<?php
/**
 * A class used to manipulate ts file, one at a time, with no caching
 *
 * @version $Id$
 * @copyright C G. Giunta 2011
 */

class eZSimpleTSLoader
{
    /**
    * Loads a ts file into in-memory representation.
    * Previous data is eliminated
    * @return boolean
    */
    function loadTranslationFile( $filename, $validate = true )
    {
        $transXML = file_get_contents( $filename );
        $tree = new DOMDOcument();
        if ( !$tree->loadXML( $transXML ) )
        {
            return false;
        }
        if ( $validate && !eZTSTranslator::validateDOMTree( $tree ) )
        {
            return false;
        }
        $treeRoot = $tree->documentElement;
        $children = $treeRoot->childNodes;
        /// @todo !important log warnings / errors?
        $this->contexts = array();
        foreach( $children as $child )
        {
            if ( $child->nodeType == XML_ELEMENT_NODE )
            {
                if ( $child->localName == "context" )
                {
                    $this->handleContextNode( $child );
                }
            }
        }
        return true;
    }

    /// Original code from ezchecktranslation.php script
    protected function handleContextNode( $context )
    {
        $contextName = null;
        $messages = array();
        foreach ( $context->childNodes as $context_child )
        {
            if ( $context_child->nodeType == XML_ELEMENT_NODE )
            {
                if ( $context_child->localName == "name" )
                {
                    $contextName = $context_child->textContent;
                }
                else if ( $context_child->localName == "message" )
                {
                    $messages[] = $context_child;
                }
            }
        }
        if ( $contextName === null )
        {
            return false;
        }
        else
        {
            $this->contexts[$contextName] = array();
            foreach( $messages as $message )
            {
                $this->handleMessageNode( $contextName, $message );
            }
        }

        return true;
    }

    /// Original code from ezchecktranslation.php script
    protected function handleMessageNode( $contextName, $message )
    {
        $source = null;
        $translation = null;
        $comment = null;
        $type = '';
        foreach( $message->childNodes as $message_child )
        {
            if ( $message_child->nodeType == XML_ELEMENT_NODE )
            {
                if ( $message_child->localName == "source" )
                {
                    $source = $message_child->textContent;
                }
                else if ( $message_child->localName == "translation" )
                {
                    $translation_el = $message_child->childNodes;
                    $type = $message_child->getAttribute( 'type' );
                    if ( $translation_el->length > 0 )
                    {
                        $translation_el = $translation_el->item( 0 );
                        $translation = $translation_el->textContent;
                    }
                }
                else if ( $message_child->localName == "comment" )
                {
                    $comment = $message_child->textContent;
                }
            }
        }
        if ( $source === null )
        {
            return false;
        }
        else
        {
            if ( $type == '' )
            {
                $this->contexts[$contextName][$source] = array( 'translation' => $translation, 'comment' => $comment );
            }
            elseif ( $type == 'unfinished' )
            {
                 $this->contexts[$contextName]['__unfinished__'][$source] = array( 'translation' => $translation, 'comment' => $comment );
            }
            elseif ( $type == 'obsolete' )
            {
                 $this->contexts[$contextName]['__obsolete__'][$source] = array( 'translation' => $translation, 'comment' => $comment );
            }
        }

        return true;
    }

    // resets in_memory representation
    function reset()
    {
        $this->contexts = array();
    }

    function contexts()
    {
        return $this->contexts;
    }

    protected $contexts = array();
}

?>