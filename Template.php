<?php

namespace dee\angularjs;

/**
 * Description of Template
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Template extends \yii\base\Widget
{

    /**
     * Starts recording a block.
     */
    public function init()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Ends recording a block.
     * This method stops output buffering and saves the rendering result as a named block in the view.
     */
    public function run()
    {
        $this->view->blocks['$templateCache'][$this->getId()] = ob_get_clean();
    }
}
