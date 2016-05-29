<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;

/**
 * Column Controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class ColumnController extends BaseController
{
    /**
     * Display columns list
     *
     * @access public
     */
    public function index()
    {
        $project = $this->getProject();
        $columns = $this->columnModel->getAll($project['id']);

        $this->response->html($this->helper->layout->project('column/index', array(
            'columns' => $columns,
            'project' => $project,
            'title' => t('Edit board')
        )));
    }

    /**
     * Show form to create a new column
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws \Kanboard\Core\Controller\PageNotFoundException
     */
    public function create(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();

        if (empty($values)) {
            $values = array('project_id' => $project['id']);
        }

        $this->response->html($this->template->render('column/create', array(
            'values' => $values,
            'errors' => $errors,
            'project' => $project,
            'title' => t('Add a new column')
        )));
    }

    /**
     * Validate and add a new column
     *
     * @access public
     */
    public function save()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();

        list($valid, $errors) = $this->columnValidator->validateCreation($values);

        if ($valid) {
            if ($this->columnModel->create($project['id'], $values['title'], $values['task_limit'], $values['description']) !== false) {
                $this->flash->success(t('Column created successfully.'));
                return $this->response->redirect($this->helper->url->to('ColumnController', 'index', array('project_id' => $project['id'])), true);
            } else {
                $errors['title'] = array(t('Another column with the same name exists in the project'));
            }
        }

        return $this->create($values, $errors);
    }

    /**
     * Display a form to edit a column
     *
     * @access public
     * @param array $values
     * @param array $errors
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $column = $this->columnModel->getById($this->request->getIntegerParam('column_id'));

        $this->response->html($this->helper->layout->project('column/edit', array(
            'errors' => $errors,
            'values' => $values ?: $column,
            'project' => $project,
            'column' => $column,
            'title' => t('Edit column "%s"', $column['title'])
        )));
    }

    /**
     * Validate and update a column
     *
     * @access public
     */
    public function update()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();

        list($valid, $errors) = $this->columnValidator->validateModification($values);

        if ($valid) {
            if ($this->columnModel->update($values['id'], $values['title'], $values['task_limit'], $values['description'])) {
                $this->flash->success(t('Board updated successfully.'));
                return $this->response->redirect($this->helper->url->to('ColumnController', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to update this board.'));
            }
        }

        return $this->edit($values, $errors);
    }

    /**
     * Move column position
     *
     * @access public
     */
    public function move()
    {
        $project = $this->getProject();
        $values = $this->request->getJson();

        if (! empty($values) && isset($values['column_id']) && isset($values['position'])) {
            $result = $this->columnModel->changePosition($project['id'], $values['column_id'], $values['position']);
            $this->response->json(array('result' => $result));
        } else {
            throw new AccessForbiddenException();
        }
    }

    /**
     * Confirm column suppression
     *
     * @access public
     */
    public function confirm()
    {
        $project = $this->getProject();

        $this->response->html($this->helper->layout->project('column/remove', array(
            'column' => $this->columnModel->getById($this->request->getIntegerParam('column_id')),
            'project' => $project,
            'title' => t('Remove a column from a board')
        )));
    }

    /**
     * Remove a column
     *
     * @access public
     */
    public function remove()
    {
        $project = $this->getProject();
        $this->checkCSRFParam();
        $column_id = $this->request->getIntegerParam('column_id');

        if ($this->columnModel->remove($column_id)) {
            $this->flash->success(t('Column removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this column.'));
        }

        $this->response->redirect($this->helper->url->to('ColumnController', 'index', array('project_id' => $project['id'])));
    }
}
