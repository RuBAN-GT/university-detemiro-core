<?php
    namespace detemiro\modules\basicNavigation;

    class item extends \detemiro\modules\navigation\item {
        use \detemiro\modules\basic\basicControl;

        protected function updateAuto($key = '') {
            if($this->page && $this->value) {
                if($page = \detemiro::pages()->get($this->value)) {
                    switch($key) {
                        case '': case 'rules':
                            if($page->rules) {
                                $this->rules = array_merge($this->rules, \detemiro\take_good_array($page->rules, true));
                            }
                        case '': case 'groups':
                            if($page->groups) {
                                $this->groups = array_merge($this->groups, \detemiro\take_good_array($page->groups, true));
                            }
                        case '': case 'title':
                            if($this->title == null) {
                                $this->title = $page->title;
                            }
                    }
                }
                elseif(($key == '' || $key == 'title') && $this->title == '') {
                    $this->title = 'Undefined';
                }
            }
            elseif(($key == '' || $key == 'title') && $this->title == '') {
                $this->title = 'Unknown';
            }
        }
    }
?>