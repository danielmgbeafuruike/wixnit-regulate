<?php
    /*
    //  A utility class for getting regulation data and checking a large data set against
    */

    namespace Wixnit\Regulate;

    use \Wixnit\Regulate\traits\Regulated;
    use Wixnit\Country;
    use Wixnit\Data\DBCollection;
    use Wixnit\Data\Filter;

    class Regulation
    {
        protected $country_code = "";
        protected $country = null;

        private $dataSet = [];
        private $object = null;
        private $type = "";

        function __construct($country, $type_or_object=null)
        {
            if($type_or_object != null)
            {
                $type = is_string($type_or_object) ? array_reverse(explode("\\", $type_or_object))[0] : (in_array($type_or_object::class, class_uses(Regulated::class)) ? $type_or_object->getType() : array_reverse(explode("\\", get_called_class($type_or_object))));
                $this->dataSet = RegulationData::Get(
                    new Filter([
                        "country"=> (($country instanceof Country) ? $country->Code : $country), 
                        "type"=>$type
                    ])
                );
            }
            else
            {
                $this->dataSet = RegulationData::Get(
                    new Filter([
                        "country"=>$country->Code
                    ])
                );
            }
        }

        /**
         * @param $list
         * @return array
         * get all the permitted items from a list of items
         */
        public function getPermitted(array | DBCollection $list) : array
        {
            $ret = [];

            for($i = 0; $i < count($list); $i++)
            {
                for($j = 0; $j < count($this->dataSet); $j++)
                {
                    if(in_array(Regulated::class, class_uses($list[$i])))
                    {
                        if(($list[$i]->Id == $this->dataSet[$j]->Item) && ($list[$i]->getType() == $this->dataSet[$j]->Type))
                        {
                            $ret[] = $list[$i];
                        }
                    }
                }
            }
            return  $ret;
        }


        /**
         * @param $object
         * @return bool
         * check if an item is regulated in the country supplied in the constructor
         */
        public function isPermitted($object=null) : bool
        {
            if(($object == null) && ($this->object != null))
            {
                if(count($this->dataSet) > 0)
                {
                    return true;
                }
            }
            else if($object != null)
            {
                for($i = 0; $i < count($this->dataSet); $i++)
                {
                    if($object->Id == $this->dataSet[$i]->Item)
                    {
                        return true;
                    }
                }
            }
            return  false;
        }

        /**
         * @param $object
         * @return bool
         * check if a type is a country regulated type
         */
        public static function Regulated($object): bool
        {
            return is_a($object, "Regulatable");
        }


        /**
         * @param $list
         * @param Country $country
         * @return array
         * inefficient way to check if a list of items are permitted in a country
         */
        public static function Filter($list, $country) : array
        {
            $ret = [];
            for($i = 0; $i < count($list); $i++)
            {
                if(in_array(Regulated::class, class_uses($list[$i])))
                {
                    if($list[$i]->isPermitted($country))
                    {
                        array_push($ret, $list[$i]);
                    }
                }
            }
            return  $ret;
        }
    }