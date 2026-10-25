<?php

class ButtonBar
{
    protected array $buttons = [];
    /**
     * Adds an action button that should activate a modal to the main button bar
     * @param $label
     * @param string|null $modalView
     * @param $target
     * @param $icon
     * @return void
     */
    public function addModalButton($label,$target, $modalView=null, $icon=null, array $dataAttrs = []):void
    {
        $btn = ['type'=>'button','label'=>$label,'iconClass'=>$icon,'toggle'=>'modal','target'=>$target,'modalView'=>$modalView];
        if (!empty($dataAttrs)) {
            $btn['dataAttrs'] = $dataAttrs;
        }
        $this->buttons[] = $btn;
    }



    protected string $name;

    /**
     * Adds an action button with a given action to the main button bar
     * @param $label
     * @param $action
     * @param $icon
     * @return void
     */
    public function addButton($label, $action, $icon=null):void
    {
        $this->buttons[] = ['type'=>'button','label'=>$label,'iconClass'=>$icon,'action'=>$action];
    }

    /**
     * Adds an action link to the main button bar
     * @param $label
     * @param $link
     * @param $icon
     * @return void
     */
    public function addLink($label, $link, $icon = null, array $attrs = []): void
    {
        $this->buttons[] = [
            'type' => 'link',
            'label' => $label,
            'link' => $link,
            'iconClass' => $icon,
            'attrs' => $attrs,
        ];
    }

    /**
     * Gets the button bar view path for the module
     * @return string
     */
    public function getButtonBarViewPath():string
    {
        return "{$this->moduleViewPath}/button-bar}";
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the modal view paths of the modal buttons
     * @return array
     */
    public  function getModals(): array
    {
        $modals = [];
        foreach ($this->buttons as $item) {
            if (isset($item['modalView'])) {
                $modals[] = $item['modalView'];
            }
        }
        return $modals;
    }



}
