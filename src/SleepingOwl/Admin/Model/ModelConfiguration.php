<?php

namespace SleepingOwl\Admin\Model;

use Config;
use Eloquent;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SleepingOwl\Admin\Http\Controllers\AdminController;
use SleepingOwl\Admin\Interfaces\DisplayInterface;
use SleepingOwl\Admin\Interfaces\FormInterface;
use SleepingOwl\Admin\Interfaces\ShowInterface;
use SleepingOwl\Admin\Repository\BaseRepository;

/**
 * Class ModelConfiguration
 * @package SleepingOwl\Admin\Model
 */
class ModelConfiguration
{

	/**
	 * @var
     */
	protected $class;

	/**
	 * @var
     */
	protected $alias;
	/**
	 * @var array
     */
	protected $alt_aliases = [];
	/**
	 * @var
     */
	protected $title;
	/**
	 * @var
     */
	protected $display;
	/**
	 * @var
     */
	protected $show;
	/**
	 * @var
     */
	protected $create;

	/**
	 * @var
     */
	protected $edit;


    /**
     * Is Model displayable?
     * @var boolean|Callable
     */
    protected $displayable = null;

    /**
     * Is Model creatable?
     * @var boolean|Callable
     */
    protected $creatable = null;

	/**
	 * Is Model editable?
	 * @var boolean|Callable
	 */
	protected $editable = null;

	/**
	 * Is Model Content editable?
	 * @var boolean|Callable
	 */
	protected $editableContent = null;

    /**
     * Is Model deletable (soft delete)?
     * @var boolean|Callable
     */
    protected $deletable = null;

    /**
     * Is Model restorable (after soft delete)?
     * @var boolean|Callable
     */
    protected $restorable = null;

    /**
     * Is Model forceDeletable (after soft delete)?
     * @var boolean|Callable
     */
    protected $forceDeletable = null;

    /**
     * Is Model showable?
     * @var boolean|Callable
     */
    protected $showable = null;

	/**
	 * @var bool|Callable
     */
	protected $delete = true;
	/**
	 * @var bool|Callable
	 */
	protected $forceDelete = true;
	/**
	 * @var bool|Callable
     */
	protected $restore = true;
	/**
	 * @var bool
     */
	protected $acls_are_active = false;

	/**
	 * @param $class
     */
	function __construct($class)
	{
		$this->class = $class;
		$this->setDefaultAlias();

		if (Config::get('admin.acls_active_by_default')) $this->acls_are_active = true;
	}

	/**
	 * @return mixed
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 * @return BaseRepository
     */
	public function repository()
	{
		return new BaseRepository($this->class);
	}

	/**
	 *
     */
	protected function setDefaultAlias()
	{
		$alias = Str::snake(Str::plural(class_basename($this->class)));
		$this->alias($alias);
	}

	/**
	 * @param null $alias
	 * @return $this
     */
	public function alias($alias = null)
	{
		if (func_num_args() == 0)
		{
			return $this->alias;
		}
		$this->alias = $alias;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function aclsAreActive()
	{
		return $this->acls_are_active;
	}

	/**
	 * @param boolean $acls_are_active
	 */
	public function setAclsAreActive($acls_are_active)
	{
		$this->acls_are_active = $acls_are_active;
	}

	/**
	 * @param null $active
	 * @return $this|bool
     */
	public function active_acls($active = null){
		if ($active == null) {
			return $this->acls_are_active;
		}

		if (is_bool($active)){
			$this->acls_are_active = $active;
		} else {
			throw new InvalidArgumentException('active parameter have to be a boolean. Given value is: '. $active);
		}
		return $this;
	}

	/**
	 * Add an alternative alias or an array of alternative aliases
	 * @param string|array $alias
	 * @return $this
     */
	public function alt_alias($alias)
	{
		$alias = (array) $alias;
		$this->alt_aliases = array_merge($this->alt_aliases,$alias);
		return $this;
	}

	/**
	 * get alt_aliases array property or set the alt_aliases array property
	 * @param null|string|array $alt_aliases
	 * @return $this|array
     */

	public function alt_aliases($alt_aliases = null)
	{
		if (func_num_args() == 0)
		{
			return $this->alt_aliases;
		}
		$alt_aliases = (array) $alt_aliases;
		$this->alt_aliases = $alt_aliases;
		return $this;
	}

	/**
	 * @param null $title
	 * @return $this
     */
	public function title($title = null)
	{
		if (func_num_args() == 0)
		{
			return $this->title;
		}
		$this->title = $title;
		return $this;
	}

	/**
	 * @param null $create
	 * @return $this|mixed|null
     */
	public function create($create = null)
	{
		if (func_num_args() == 0)
		{
			return $this->getCreate();
		}
		$this->create = $create;
		return $this;
	}

	/**
	 * @param null $show
	 * @return $this|mixed|null
     */
	public function show($show = null)
	{
		if (func_num_args() == 0 || is_numeric($show))
		{
			return $this->getShow($show);
		}
		$this->show = $show;
		return $this;
	}

	/**
	 * @param null $edit
	 * @return $this|mixed|null
     */
	public function edit($edit = null)
	{
		if ((func_num_args() == 0) || is_numeric($edit))
		{
			return $this->getEdit($edit);
		}
		$this->edit = $edit;
		return $this;
	}

    /**
     * Check permissions and policies (if needed => id !=null)
     *
     * @param $id
     * @param $action
     * @return bool
     */
    protected function checkPermissionAndPolicies($id, $action)
    {
        if ($id != null) {
            if ($id instanceof Eloquent || ! is_int($id)) {
                return (AdminController::checkPermissionAccess($this, $action) &&
                    AdminController::checkPolicyAccessByInstance($this, $action, $id));
            } else {
                return (AdminController::checkPermissionAccess($this, $action) &&
                    AdminController::checkPolicyAccess($this, $action, $id));
            }
        } else {
            return AdminController::checkPermissionAccess($this, $action);
        }
    }

    /**
     * Is displayable?
     * @param null $displayable
     * @param null $id
     * @return $this|bool|Callable|mixed
     */
    public function displayable($displayable = null, $id = null){

        if ($displayable == null) {
            if (is_callable($this->displayable))
            {
                return call_user_func($this->displayable);
            }
            if ($this->displayable != null) {
                return $this->displayable;
            }
            return $this->checkPermissionAndPolicies($id, 'retrieve');
        }
        $this->displayable = $displayable;
        return $this;
    }

    /**
     * Is creatable?
     * @param null $creatable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function creatable($creatable = null, $id = null){

        if ($creatable == null) {
            if (is_callable($this->creatable))
            {
                return call_user_func($this->creatable);
            }
            if ($this->creatable != null) {
                return $this->creatable;
            }
            return $this->checkPermissionAndPolicies($id, 'create');
        }
        $this->$creatable = $creatable;
        return $this;
    }

    /**
     * Is editable?
     * @param null $editable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function editable($editable = null, $id = null){

		if ($editable == null) {
			if (is_callable($this->editable))
			{
				return call_user_func($this->editable);
			}
			if ($this->editable != null) {
				return $this->editable;
			}
            return $this->checkPermissionAndPolicies($id, 'edit');
        }
		$this->editable = $editable;
		return $this;
	}

    /**
     * Is the content editable?
     * @param null $editableContent
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function editableContent($editableContent = null, $id = null){

		if ($editableContent == null) {
			if (is_callable($this->editableContent))
			{
				return call_user_func($this->editableContent);
			}
			if ($this->editableContent != null) {
				return $this->editableContent;
			}
            return $this->checkPermissionAndPolicies($id, 'editContent');
        }
		$this->$editableContent = $editableContent;
		return $this;
	}

    /**
     * Is deletable?
     * @param null $deletable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function deletable($deletable = null, $id = null){

        if ($deletable == null) {
            if (is_callable($this->deletable))
            {
                return call_user_func($this->deletable);
            }
            if ($this->deletable != null) {
                return $this->deletable;
            }
            return $this->checkPermissionAndPolicies($id, 'destroy');
        }
        $this->deletable = $deletable;
        return $this;
    }

    /**
     * Is restorable?
     *
     * @param null $restorable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function restorable($restorable = null, $id = null){

        if ($restorable == null) {
            if (is_callable($this->restorable))
            {
                return call_user_func($this->restorable);
            }
            if ($this->restorable != null) {
                return $this->restorable;
            }
            return $this->checkPermissionAndPolicies($id, 'restore');
        }
        $this->restorable = $restorable;
        return $this;
    }

    /**
     * Is forceDeletable?
     *
     * @param null $forceDeletable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function forceDeletable($forceDeletable = null, $id = null){

        if ($forceDeletable == null) {
            if (is_callable($this->forceDeletable))
            {
                return call_user_func($this->forceDeletable);
            }
            if ($this->forceDeletable != null) {
                return $this->forceDeletable;
            }
            return $this->checkPermissionAndPolicies($id, 'forceDestroy');
        }
        $this->forceDeletable = $forceDeletable;
        return $this;
    }

    /**
     * Is showable?
     *
     * @param null $showable
     * @param null|integer|Eloquent $id
     * @return $this|bool|Callable|mixed
     */
    public function showable($showable = null, $id = null){

        if ($showable == null) {
            if (is_callable($this->showable))
            {
                return call_user_func($this->showable);
            }
            if ($this->showable != null) {
                return $this->showable;
            }
            return $this->checkPermissionAndPolicies($id, 'show');
        }
        $this->showable = $showable;
        return $this;
    }


    /**
	 * @param $callback
	 * @return $this
     */
	public function createAndEdit($callback)
	{
		$this->create($callback);
		$this->edit($callback);
		return $this;
	}

	/**
	 * @param $callback
	 * @return $this
     */
	public function createAndEditAndShow($callback)
	{
		$this->create($callback);
		$this->edit($callback);
		$this->show($callback);
		return $this;
	}

	/**
	 * @param null $delete
	 * @return $this|bool|mixed
     */
	public function delete($delete = null)
	{
		if ((func_num_args() == 0) || is_numeric($delete))
		{
			return $this->getDelete($delete);
		}
		$this->delete = $delete;
		return $this;
	}

	/**
	 * @param null $forceDelete
	 * @return $this|bool|mixed
	 */
	public function forceDelete($forceDelete = null)
	{
		if ((func_num_args() == 0) || is_numeric($forceDelete))
		{
			return $this->getForceDelete($forceDelete);
		}
		$this->forceDelete = $forceDelete;
		return $this;
	}

	/**
	 * @param null $restore
	 * @return $this|bool|mixed
     */
	public function restore($restore = null)
	{
		if ((func_num_args() == 0) || is_numeric($restore))
		{
			return $this->getRestore($restore);
		}
		$this->restore = $restore;
		return $this;
	}

	/**
	 * @param null $display
	 * @return $this|mixed
     */
	public function display($display = null)
	{
		if (func_num_args() == 0)
		{
			return $this->getDisplay();
		}
		$this->display = $display;
		return $this;
	}

	/**
	 * @return mixed
     */
	protected function getDisplay()
	{
		$display = call_user_func($this->display);
		if ($display instanceof DisplayInterface)
		{
			$display->setClass($this->class);
			$display->initialize();
		}
		return $display;
	}


	/**
	 * @param $id
	 * @return mixed|null
     */
	protected function getShow($id)
	{
		if (is_null($this->show))
		{
			return null;
		}
		$show = call_user_func($this->show, $id);
		if ($show instanceof DisplayInterface)
		{
			$show->setClass($this->class);
			$show->initialize();
		}
		if ($show instanceof FormInterface)
		{
			$show->setAction($this->storeUrl());
		}
		return $show;
	}

	/**
	 * @return mixed|null
     */
	protected function getCreate()
	{
		if (is_null($this->create))
		{
			return null;
		}
		$create = call_user_func($this->create, null);
		if ($create instanceof DisplayInterface)
		{
			$create->setClass($this->class);
			$create->initialize();
		}
		if ($create instanceof FormInterface)
		{
			$create->setAction($this->storeUrl());
		}
		return $create;
	}

	/**
	 * @param $id
	 * @return mixed|null
     */
	protected function getEdit($id)
	{
		if (is_null($this->edit))
		{
			return null;
		}
		$edit = call_user_func($this->edit, $id);
		if ($edit instanceof DisplayInterface)
		{
			$edit->setClass($this->class);
			$edit->initialize();
		}
		return $edit;
	}

	/**
	 * @param $id
	 * @return $this|mixed|null|ModelConfiguration
     */
	public function fullEdit($id)
	{
		$edit = $this->edit($id);
		if ($edit instanceof FormInterface)
		{
			$edit->setAction($this->updateUrl($id));
			$edit->setId($id);
		}
		return $edit;
	}

	/**
	 * @param $id
	 * @return $this|mixed|null|ModelConfiguration
     */
	public function fullShow($id)
	{
		$show = $this->show($id);
		if ($show instanceof ShowInterface || $show instanceof FormInterface)
		{
			$show->setId($id);
		}
		return $show;
	}

	/**
	 * @param $id
	 * @return bool|mixed
     */
	protected function getDelete($id)
	{
		if (is_callable($this->delete))
		{
			return call_user_func($this->delete, $id);
		}
		return $this->delete;
	}

	/**
	 * @param $id
	 * @return bool|mixed
	 */
	protected function getForceDelete($id)
	{
		if (is_callable($this->forceDelete))
		{
			return call_user_func($this->forceDelete, $id);
		}
		return $this->forceDelete;
	}

	/**
	 * @param $id
	 * @return bool|mixed
     */
	protected function getRestore($id)
	{
		if (is_callable($this->restore))
		{
			return call_user_func($this->restore, $id);
		}
		return $this->restore;
	}

	/**
	 * @param array $parameters
	 * @return string
     */
	public function displayUrl($parameters = [])
	{
		array_unshift($parameters, $this->alias());
		return route('admin.model', $parameters);
	}

	/**
	 * @param $id
	 * @return string
     */
	public function showUrl($id)
	{
		return route('admin.model.show', [$this->alias(), $id]);
	}

	/**
	 * @param array $parameters
	 * @return string
     */
	public function createUrl($parameters = [])
	{
		array_unshift($parameters, $this->alias());
		return route('admin.model.create', $parameters);
	}

	/**
	 * @return string
     */
	public function storeUrl()
	{
		return route('admin.model.store', $this->alias());
	}

	/**
	 * @param $id
	 * @return string
     */
	public function editUrl($id)
	{
		return route('admin.model.edit', [$this->alias(), $id]);
	}

	/**
	 * @param $id
	 * @return string
     */
	public function updateUrl($id)
	{
		return route('admin.model.update', [$this->alias(), $id]);
	}

	/**
	 * @param $id
	 * @return string
     */
	public function deleteUrl($id)
	{
		return route('admin.model.destroy', [$this->alias(), $id]);
	}

    /**
	 * @param $id
	 * @return string
     */
	public function forceDeleteUrl($id)
	{
		return route('admin.model.forceDestroy', [$this->alias(), $id]);
	}

	/**
	 * @param $id
	 * @return string
     */
	public function restoreUrl($id)
	{
		return route('admin.model.restore', [$this->alias(), $id]);
	}

}
