<?php

namespace Backend\Modules\Instagram\Actions;

use Backend\Core\Engine\Base\ActionDelete;
use Backend\Core\Engine\Model;
use Backend\Modules\Instagram\Engine\Model as BackendInstagramModel;

/**
 * This is the delete-action, it deletes an item
 *
 * @author Jesse Dobbelaere <jesse@dobbelae.re>
 */
class Delete extends ActionDelete
{
    /**
     * Execute the action
     */
    public function execute()
    {
        $this->id = $this->getParameter('id', 'int');

        // Does the item exist?
        if ($this->id !== null && BackendInstagramModel::exists($this->id)) {
            parent::execute();
            $this->record = (array) BackendInstagramModel::get($this->id);

            if ($this->record['locked'] == 'Y') {
                $this->redirect(Model::createURLForAction('Index') . '&error=is-locked');
            }

            // Delete the file
            BackendInstagramModel::delete($this->id);
            Model::triggerEvent($this->getModule(), 'after_delete', array('id' => $this->id));

            $this->redirect(
                Model::createURLForAction('Index') . '&report=deleted&var=' .
                urlencode($this->record['title'])
            );
        } else {
            $this->redirect(Model::createURLForAction('Index') . '&error=non-existing');
        }
    }
}
