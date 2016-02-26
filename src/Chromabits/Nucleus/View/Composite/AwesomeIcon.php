<?php

namespace Chromabits\Nucleus\View\Composite;

use Chromabits\Nucleus\Data\ArrayList;
use Chromabits\Nucleus\Foundation\BaseObject;
use Chromabits\Nucleus\Support\Html;
use Chromabits\Nucleus\View\Common\Italic;
use Chromabits\Nucleus\View\Interfaces\RenderableInterface;
use Chromabits\Nucleus\View\Interfaces\SafeHtmlProducerInterface;
use Chromabits\Nucleus\View\SafeHtmlWrapper;

/**
 * Class AwesomeIcon.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Nucleus\View\Composite
 */
class AwesomeIcon extends BaseObject implements
    RenderableInterface,
    SafeHtmlProducerInterface
{
    /**
     * @var string
     */
    protected $icon;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var bool
     */
    protected $spin;

    /**
     * @var bool
     */
    protected $fixedWidth;

    /**
     * @var bool
     */
    protected $list;

    /**
     * @var bool
     */
    protected $bordered;

    /**
     * @var string|null
     */
    protected $pull;

    /**
     * @var int
     */
    protected $rotation;

    /**
     * @var string|null
     */
    protected $flip;

    /**
     * Construct an instance of a AwesomeIcon.
     *
     * @param string $icon
     * @param int $size
     * @param bool $spin
     */
    public function __construct($icon, $size = 0, $spin = false)
    {
        parent::__construct();

        $this->icon = $icon;
        $this->size = $size;
        $this->spin = $spin;

        $this->fixedWidth = false;
        $this->list = false;
        $this->bordered = false;
        $this->pull = null;
        $this->rotation = 0;
        $this->flip = null;
    }

    /**
     * @return AwesomeIcon
     */
    public function setFixedWidth()
    {
        $copy = clone $this;

        $this->fixedWidth = true;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function setList()
    {
        $copy = clone $this;

        $this->list = true;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function setBordered()
    {
        $copy = clone $this;

        $this->bordered = true;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function pullRight()
    {
        $copy = clone $this;

        $this->pull = 'right';

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function pullLeft()
    {
        $copy = clone $this;

        $this->pull = 'left';

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function rotate90()
    {
        $copy = clone $this;

        $this->rotation = 90;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function rotate180()
    {
        $copy = clone $this;

        $this->rotation = 180;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function rotate270()
    {
        $copy = clone $this;

        $this->rotation = 270;

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function flipHorizontally()
    {
        $copy = clone $this;

        $this->flip = 'horizontal';

        return $copy;
    }

    /**
     * @return AwesomeIcon
     */
    public function flipVertically()
    {
        $copy = clone $this;

        $this->flip = 'vertical';

        return $copy;
    }

    /**
     * Render the object into a string.
     *
     * @return string
     */
    public function render()
    {
        $classes = ArrayList::of([
            'fa',
            vsprintf('fa-%s', [$this->icon])
        ]);

        // Spin class (fa-spin)
        if ($this->spin) {
            $classes = $classes->append(ArrayList::of(['fa-spin']));
        }

        // Size classes (fa-lg, fa-2x, fa-3x...)
        if ($this->size === 1) {
            $classes = $classes->append(ArrayList::of(['fa-lg']));
        } elseif ($this->size > 1 && $this->size < 6) {
            $classes = $classes->append(ArrayList::of([
                vsprintf('fa-%dx', [$this->size])
            ]));
        }

        // Fixed-width class (fa-fw)
        if ($this->fixedWidth) {
            $classes = $classes->append(ArrayList::of(['fa-fw']));
        }

        // List icons class (fa-li)
        if ($this->list) {
            $classes = $classes->append(ArrayList::of(['fa-li']));
        }

        // Border class (fa-border)
        if ($this->bordered) {
            $classes = $classes->append(ArrayList::of(['fa-border']));
        }

        // Pull classes (fa-pull-right, fa-pull-left)
        if ($this->pull) {
            $classes = $classes->append(ArrayList::of([
                vsprintf('fa-pull-%s', [$this->pull])
            ]));
        }

        // Rotation classes
        switch ($this->rotation) {
            case 90:
                $classes = $classes->append(ArrayList::of(['fa-rotate-90']));
                break;
            case 180:
                $classes = $classes->append(ArrayList::of(['fa-rotate-180']));
                break;
            case 270:
                $classes = $classes->append(ArrayList::of(['fa-rotate-270']));
                break;
        }

        // Flip classes
        switch ($this->flip) {
            case 'horizontal':
                $classes = $classes->append(ArrayList::of([
                    'fa-flip-horizontal'
                ]));
                break;
            case 'vertical':
                $classes = $classes->append(ArrayList::of([
                    'fa-flip-horizontal'
                ]));
                break;
        }

        return (new Italic([
            'class' => $classes->join(' '),
        ]))->render();
    }

    /**
     * Get a safe HTML version of the contents of this object.
     *
     * @return SafeHtmlWrapper
     */
    public function getSafeHtml()
    {
        return Html::safe($this->render());
    }
}
