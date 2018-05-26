<?php
    namespace detemiro\modules\basic;

    /**
     * Управление группами
     *
     * @services groups
     */
    class groups extends \detemiro\modules\database\data {
        protected $primary = array('code');

        /**
         * Объект прав
         * 
         * @var \detemiro\modules\basic\rules $rules
         */
        protected $rules;

        protected $tmp;

        /**
         * Инициализация
         * 
         * @param \detemiro\modules\database\dbActions $db
         * @param \detemiro\modules\basic\rules        $rules
         */
        public function __construct(\detemiro\modules\database\dbActions $db, \detemiro\modules\basic\rules $rules) {
            parent::__construct($db);

            $this->rules = $rules;
        }

        /**
         * Получение прав определённый группы
         * 
         * @param  string $code
         *
         * @return array
         */
        public function getRules($code) {
            $rules = null;

            if(is_string($code)) {
                $rules = $this->db->actionData("SELECT code FROM {$this->db->prefix}rules JOIN {$this->db->prefix}groups_rules ON code = rule_code AND group_code = ?", 0, false, false, array($code));
            }

            return ($rules) ? $rules : array();
        }

        public function getItems(array $par = array()) {
            $custom = array(
                'limit'  => null,
                'offset' => null,
                'rules'  => null,
                'order'  => null,
                'cond'   => null
            );

            if($par) {
                $custom = array_merge($custom, array_intersect_key($par, $custom));

                $extra = array();
                if($custom['rules']) {
                    $custom['rules'] = \detemiro\take_good_array($custom['rules'], true);
                    $extra[] = array(
                        'manual'     => "code IN (SELECT group_code FROM {$this->db->prefix}groups_rules WHERE rule_code IN (" . implode(',', array_pad(array(), count($custom['rules']), '?')) . "))",
                        'manualArgs' => $custom['rules']
                    );
                }

                if($custom['cond']) {
                    $custom['cond'] = array($custom['cond'], $extra);
                }
                else {
                    $custom['cond'] = $extra;
                }
            }

            if($this->db->driver == 'mysql') {
                $custom['cols']    = 'G.*,GROUP_CONCAT(DISTINCT(GR.rule_code)) as rules';
                $custom['groupBy'] = 'G.code';
                $custom['join']    = "G LEFT OUTER JOIN {$this->db->prefix}groups_rules GR";
                $custom['on']      = 'GR.group_code = G.code';

                if($res = $this->get($custom)) {
                    foreach($res as &$item) {
                        $this->__getHandler($item);
                    }

                    return $res;
                }
            }
            elseif($res = $this->get($custom)) {
                foreach($res as &$item) {
                    $item['rules'] = $this->getRules($item['code']);

                    $this->__getHandler($item);
                }

                return $res;
            }

            return null;
        }
        public function getItem($code) {
            if($param = $this->getCond($code)) {
                if($this->db->driver == 'mysql') {
                    if($res = $this->get(array(
                        'cols'    => 'G.*,GROUP_CONCAT(DISTINCT(GR.rule_code)) as rules',
                        'groupBy' => 'G.code',
                        'join'    => "G LEFT OUTER JOIN {$this->db->prefix}groups_rules GR",
                        'on'      => 'GR.group_code = G.code',
                        'oneRow'  => 0,
                        'cond'    => $param
                    ))
                    ) {
                        $this->__getHandler($res);

                        return $res;
                    }
                }
                elseif($res = $this->get(array('oneRow' => 0, 'cond' => $param))) {
                    $res['rules'] = $this->getRules($res['code']);

                    $this->__getHandler($res);

                    return $res;
                }
            }

            return null;
        }

        /**
         * Проверка наличия права у группы
         * 
         * @param  string $code
         * @param  string $rule
         *
         * @return bool
         */
        public function checkRule($code, $rule) {
            return (is_string($code) && is_string($rule) && $this->db->count('groups_rules', array(
                array(
                    'param' => 'group_code',
                    'value' => $code
                ),
                array(
                    'param' => 'rule_code',
                    'value' => $rule
                )
            )) > 0);
        }

        /**
         * Добавление права для группы
         * 
         * @param  string $code
         * @param  string $rule
         *
         * @return bool
         */
        public function addRule($code, $rule) {
            if(is_string($code) && $this->rules->exists($rule)) {
                return $this->db->insert('groups_rules', array(
                    'group_code' => $code,
                    'rule_code'  => $rule
                ));
            }

            return false;
        }

        /**
         * Удаление права для группы
         * 
         * @param  string $code
         * @param  string $rule
         *
         * @return bool
         */
        public function removeRule($code, $rule) {
            if(is_string($code) && is_string($rule)) {
                return $this->db->delete('groups_rules', array(
                    array('param' => 'group_code', 'value' => $code),
                    array('param' => 'rule_code', 'value' => $rule)
                ));
            }

            return false;
        }

        //Обработчики
        protected function __getHandler(array &$res) {
            parent::__getHandler($res);

            if(isset($res['rules']) == false) {
                $res['rules'] = array();
            }
        }

        public function __get_rules(&$value) {
            if(is_string($value)) {
                $value = array_unique(explode(',', $value));
            }
        }

        protected function __addBeforeHandler(array &$par) {
            if(parent::__addBeforeHandler($par) === false) {
                return false;
            }
            elseif(isset($par['rules'])) {
                $this->tmp = $par['rules'];

                unset($par['rules']);
            }
            else {
                $this->tmp = null;
            }

            return true;
        }
        protected function __addSuccessHandler(array $par) {
            if($this->tmp !== null) {
                foreach(\detemiro\take_good_array($this->tmp, true) as $rule) {
                    $this->addRule($par['code'], $rule);
                }

                $this->tmp = null;
            }
        }
        protected function __updateBeforeHandler(array &$par, array $cond = null) {
            if(parent::__updateBeforeHandler($par) === false) {
                return false;
            }
            elseif(array_key_exists('rules', $par)) {
                if($par['rules']) {
                    $this->tmp = $par['rules'];
                }
                else {
                    $this->tmp = array();
                }

                unset($par['rules']);
            }
            else {
                $this->tmp = null;
            }

            return true;
        }
        protected function __updateSuccessHandler(array $par, array $cond = null) {
            if($this->tmp !== null) {
                if($all = $this->getItems(array('cond' => $cond))) {
                    foreach($all as $group) {
                        foreach(\detemiro\take_good_array($this->tmp, true) as $rule) {
                            $key = array_search($rule, $group['rules']);
                            if($key === false) {
                                $this->addRule($group['code'], $rule);
                            }
                            else {
                                unset($group['rules'][$key]);
                            }
                        }

                        if(
                            $group['rules'] &&
                            ($args = implode(',', array_pad(array(), count($group['rules']), '?')))
                        )
                        {
                            $this->db->delete('groups_rules', array(
                                array(
                                    'param' => 'group_code',
                                    'value' => $group['code']
                                ),
                                array(
                                    'manual'     => "rule_code IN ($args)",
                                    'manualArgs' => $group['rules']
                                )
                            ));
                        }
                    }
                }

                $this->tmp = null;
            }
        }
    }
?>