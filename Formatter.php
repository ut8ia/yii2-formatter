<?php

namespace app\modules\desk\components;

use yii\base\InvalidCallException;
use yii\base\ViewNotFoundException;
use yii\web\View;

/**
 * Class Formatter
 * @package app\modules\desk\components
 */
class Formatter extends \yii\i18n\Formatter
{


    const DEFAULT_VIEW = 'default';

    public $partialsPath = '@app/modules/desk/views/partials/';


    /**
     * Usage
     * <?= $form->field($model, 'course_id')->dropDownList(
     * Yii::$app->formatter->asPairs(
     * Courses::class,
     * ['status' => [Courses::STATUS_OPEN, Courses::STATUS_PENDING]],
     * 'id',
     * ['view' => 'selector'])
     * );
     * ?>
     *
     *
     * @param string $className
     * @param null $where
     * @param null $keyName
     * @param null $options
     * @return array
     */
    public function asPairs($className, $where = null, $keyName = null, $options = null)
    {
        $query = $className::find();
        if ($where) {
            $query->filterWhere($where);
        }
        $records = $query->all();

        if (!$records) {
            return [];
        }

        return $this->asObjectPairs($records, $keyName, $options );
    }


    /**
     * @param  \yii\db\ActiveQuery[] $records
     * @param null $keyName
     * @param null $options
     * @return array
     */
    public function asObjectPairs($records, $keyName = null, $options = null)
    {
        $ans = [];
        if (empty($records)) {
            return $ans;
        }

        foreach ($records as $record) {

            $value = $options ? ['object' => $record] + $options : ['object' => $record];
            $ans += $this->asPair($value, $keyName);
        }
        return $ans;
    }

    /**
     * @param mixed $value
     * @param null $keyName
     * @return array
     */
    public function asPair($value, $keyName = null)
    {
        $keyName = $keyName ?: 'id';
        $key = isset($value['object']) ? $value['object']->$keyName : $value->$keyName;
        return [$key => $this->asObject($value)];
    }

    /**
     * @param object|array $value
     * @return null|string
     */
    public function asObject($value)
    {

        if (empty($value)) {
            return null;
        }

        $view = (isset($value['view'])) ? $value['view'] : null;
        $object = (isset($value['object'])) ? $value['object'] : null;
        $options = (isset($value['options'])) ? $value['options'] : null;

        // condition signature - no huge if-else tree
        $a = (int)(is_array($value));
        $v = (int)(!empty($view));
        $o = (int)(!empty($object));
        $ao = (int)(is_array($object));

        switch ($a . $v . $o . $ao) {
            case '0000':
                return $this->renderByClassname($value, self::DEFAULT_VIEW);
            case '1000':
                return $this->renderArray($value);
            case "1010":
                return $this->renderByClassname($object, self::DEFAULT_VIEW);
            case "1011":
                return $this->renderArray($object);
            case '1110':
                return $this->renderByClassname($object, $view);
            case '1111':
                return $this->renderArrayView($object, $view);
            default:
                return null;
        }
    }

    /**
     * @param object $object
     * @param string $view
     * @return string
     * @throws InvalidCallException
     * @throws ViewNotFoundException
     * @throws \ReflectionException
     */
    public function renderByClassname($object, $view)
    {
        $class = ((new \ReflectionClass($object))->getShortName());
        return (new  View())->render($this->partialsPath . $class . '/' . $view, ['model' => $object]);
    }

    /**
     * @param Object $object
     * @param string $view
     * @return string
     * @throws InvalidCallException
     * @throws ViewNotFoundException
     */
    public function renderView($object, $view)
    {
        return (new  View())->render($this->partialsPath . $view, ['model' => $object]);
    }

    /**
     * @param array $array
     * @return string
     */
    public function renderArray($array)
    {
        $out = '';
        foreach ($array as $object) {
            $out .= $this->renderByClassname($object, self::DEFAULT_VIEW);
        }
        return $out;
    }

    /**
     * @param array $array
     * @param string $view
     * @return string
     */
    public function renderArrayView($array, $view)
    {
        $out = '';
        foreach ($array as $item) {
            $out .= $this->renderView($item, $view);
        }
        return $out;
    }


}