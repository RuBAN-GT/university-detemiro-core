<?php
    namespace detemiro\modules\basicNavigation;

    /**
     * Класс навигацииs
     */
    class nav extends \detemiro\modules\navigation\nav {
        protected $object = '\detemiro\modules\basicNavigation\item';

        /**
         * Получение отсортированного списка элементов, разрешённых для пользователя
         *
         * @return array
         */
        public function getSortedAllowList() {
            $res = array();

            foreach($this->list as $key=>$item) {
                if($item->isAllow()) {
                    $res[$key] = $item;
                }
            }

            $this->sortPriority($res);

            return $res;
        }
    }
?>