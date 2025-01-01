<?php

    /*
    //  Any model that uses this trait will be able to filter itself from countries where it cannot be used
    */

    namespace Wixnit\Regulate\traits;

    use Wixnit\Regulate\RegulationData;
    use ReflectionClass;
    use Wixnit\Country;
    use Wixnit\Data\Filter;

    trait Regulated
    {
        /**
         * @return array
         * @comment get a list of countries where the item is permitted
         */
        public function getPermittedCountries(): array
        {
            $ret = [];
            $permitted = RegulationData::Get(
                new Filter([
                    "type"=>$this->getType(), 
                    "item"=>$this->Id
                ])
            );

            for($i = 0; $i < count($permitted); $i++)
            {
                array_push($ret, $permitted[$i]->Country);
            }
            return $ret;
        }

        /**
         * @param $country
         * @return void
         * @comment add a country to the allowed list
         */
        public function allowIn($country)
        {
            if($this->Id != "")
            {
                $countryCode = ($country instanceof Country) ? $country->Code : strval($country);

                if((RegulationData::Count(new Filter(["country"=>$countryCode, "type"=>$this->getType(), "item"=>$this->Id])) <= 0) && ($countryCode != ""))
                {
                    $r = new RegulationData();
                    $r->Country = $countryCode;
                    $r->Item = $this->Id;
                    $r->Type = $this->getType();
                    $r->Save();
                }
            }
        }

        /**
         * @param $country
         * @return void
         * @commet remove a country from the list of allowed countries of a model
         */
        public function disallowIn($country)
        {
            $countryCode = ($country instanceof Country) ? $country->Code : strval($country);

            $r = RegulationData::Get(new Filter(["country"=>$countryCode, "type"=>$this->getType(), "item"=>$this->Id]));
            for($i = 0; $i < count($r); $i++)
            {
                $r[$i]->Delete();
            }
        }

        /**
         * @param $country
         * @return bool
         * check if the item is permitted in a country
         */
        public function isPermitted($country): bool
        {
            $countryCode = ($country instanceof Country) ? $country->Code : strval($country);
            return RegulationData::Count(new Filter(["country"=>$countryCode, "type"=>$this->getType(), "item"=>$this->Id])) > 0;
        }

        /**
         * @return string
         * get the class name from an instance of the object
         */
        public function getType(): string
        {
            return (new ReflectionClass($this))->getShortName();
        }

        /**
         * @return string
         * get the class name from the general class declaration
         */
        public static function qualifiedName(): string
        {
            return get_called_class();
        }
    }