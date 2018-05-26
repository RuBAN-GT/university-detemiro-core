<?php
    namespace detemiro\modules\basic;

    /**
     * Управление пользователями
     *
     * @services users
     */
    class users extends \detemiro\modules\database\data {
        protected $sequence = 'users_id_seq';

        /**
         * Объект прав
         * 
         * @var \detemiro\modules\basic\rules $rules
         */
        protected $rules;

        /**
         * Объект групп
         * 
         * @var \detemiro\modules\basic\groups $groups
         */
        protected $groups;

        protected $tmpRules;
        protected $tmpGroups;

        public function __construct(\detemiro\modules\database\dbActions $db, \detemiro\modules\basic\rules $rules, \detemiro\modules\basic\groups $groups) {
            parent::__construct($db);

            $this->rules  = $rules;
            $this->groups = $groups;
        }

        /**
         * Получение групп пользователя по его ID
         * 
         * @param  int   $ID
         *
         * @return array
         */
        public function getGroups($ID) {
            $groups = null;

            if(is_numeric($ID)) {
                $groups = $this->db->actionData("SELECT code FROM {$this->db->prefix}groups JOIN {$this->db->prefix}users_groups ON code = group_code AND user_id = ?", 0, false, false, array($ID));
            }

            return ($groups) ? $groups : array();
        }

        /**
         * Получение прав пользователя по его ID
         *
         * @param  int  $ID
         * @param  bool $groups Добавление прав в результат со всех групп данного пользователя
         *
         * @return array
         */
        public function getRules($ID, $groups = true) {
            $rules = null;

            if(is_numeric($ID)) {
                $args = array($ID);

                $rules = "SELECT code FROM {$this->db->prefix}rules JOIN {$this->db->prefix}users_rules ON code = rule_code AND user_id = ?";

                if($groups) {
                    $rules .= "\nUNION\n";

                    $groups = "SELECT code FROM {$this->db->prefix}groups JOIN {$this->db->prefix}users_groups ON code = group_code AND user_id = ?";
                    $args[] = $ID;

                    $rules .= "SELECT code FROM {$this->db->prefix}rules JOIN {$this->db->prefix}groups_rules ON code = rule_code AND group_code IN ($groups)";
                }

                $rules = $this->db->actionData($rules, 0, false, false, $args);
            }

            return ($rules) ? $rules : array();
        }

        public function getItems(array $par = array()) {
            $custom = array(
                'limit'  => null,
                'offset' => null,
                'order'  => null,
                'cond'   => null,
                'groups' => null,
                'rules'  => null,
                'full'   => false
            );

            if($par) {
                $custom = array_merge($custom, array_intersect_key($par, $custom));

                $extra = array();
                if($custom['groups']) {
                    $custom['groups'] = \detemiro\take_good_array($custom['groups'], true);
                    $extra[] = array(
                        'manual'     => "id IN (SELECT user_id FROM {$this->db->prefix}users_groups WHERE group_code IN (" . implode(',', array_pad(array(), count($custom['groups']), '?')) . "))",
                        'manualArgs' => $custom['groups']
                    );
                }
                if($custom['rules']) {
                    $custom['rules'] = \detemiro\take_good_array($custom['rules'], true);
                    $extra[] = array(
                        'manual'     => "id IN (SELECT user_id FROM {$this->db->prefix}users_rules WHERE rule_code IN (" . implode(',', array_pad(array(), count($custom['rules']), '?')) . "))",
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
                $custom['groupBy'] = 'U.id';
                if($custom['full']) {
                    $custom['cols'] = 'U.*, GROUP_CONCAT(DISTINCT(G.group_code)) as groups, CONCAT_WS(\',\', GROUP_CONCAT(DISTINCT(GR.rule_code)), GROUP_CONCAT(DISTINCT(UR.rule_code))) as rules';
                    $custom['join'] = array(
                        "U LEFT OUTER JOIN {$this->db->prefix}users_groups G ON G.user_id = U.id",
                        "LEFT OUTER JOIN {$this->db->prefix}groups_rules GR ON GR.group_code = G.group_code",
                        "LEFT OUTER JOIN {$this->db->prefix}users_rules UR ON UR.user_id = U.id"
                    );
                }
                else {
                    $custom['cols'] = 'U.*, GROUP_CONCAT(DISTINCT(G.group_code)) as groups, GROUP_CONCAT(DISTINCT(UR.rule_code)) as rules';
                    $custom['join'] = array(
                        "U LEFT OUTER JOIN {$this->db->prefix}users_groups G ON G.user_id = U.id",
                        "LEFT OUTER JOIN {$this->db->prefix}users_rules UR ON UR.user_id = U.id"
                    );
                }

                if($res = $this->get($custom)) {
                    foreach($res as &$item) {
                        $this->__getHandler($item);
                    }

                    return $res;
                }
            }
            elseif($res = $this->get($custom)) {
                foreach($res as &$user) {
                    $user['groups'] = $this->getGroups($user['id']);
                    $user['rules']  = $this->getRules($user['id'], true);

                    $this->__getHandler($user);
                }

                return $res;
            }

            return null;
        }

        /**
         * Получение пользователя
         *
         * @param  int  $id
         * @param  bool $full Поля rules (если выбрано) будет содержать ещё и права групп
         *
         * @return array|null
         */
        public function getItem($id, $full = false) {
            if($param = $this->getCond($id)) {
                $query = array(
                    'oneRow' => 0,
                    'cond'   => $param
                );

                if($this->db->driver == 'mysql') {
                    $query['groupBy'] = 'U.id';

                    if($full) {
                        $query['cols'] = 'U.*, GROUP_CONCAT(DISTINCT(G.group_code)) as groups, CONCAT_WS(\',\', GROUP_CONCAT(DISTINCT(GR.rule_code)), GROUP_CONCAT(DISTINCT(UR.rule_code))) as rules';
                        $query['join'] = array(
                            "U LEFT OUTER JOIN {$this->db->prefix}users_groups G ON G.user_id = U.id",
                            "LEFT OUTER JOIN {$this->db->prefix}groups_rules GR ON GR.group_code = G.group_code",
                            "LEFT OUTER JOIN {$this->db->prefix}users_rules UR ON UR.user_id = U.id"
                        );
                    }
                    else {
                        $query['cols'] = 'U.*, GROUP_CONCAT(DISTINCT(G.group_code)) as groups, GROUP_CONCAT(DISTINCT(UR.rule_code)) as rules';
                        $query['join'] = array(
                            "U LEFT OUTER JOIN {$this->db->prefix}users_groups G ON G.user_id = U.id",
                            "LEFT OUTER JOIN {$this->db->prefix}users_rules UR ON UR.user_id = U.id"
                        );
                    }

                    if($res = $this->get($query)) {
                        $this->__getHandler($res);

                        return $res;
                    }
                }
                elseif($res = $this->get($query)) {
                    $res['groups'] = $this->getGroups($res['id']);
                    $res['rules']  = $this->getRules($res['id'], $full);

                    $this->__getHandler($res);

                    return $res;
                }
            }

            return null;
        }


        /**
         * Проверка прав пользователя по его ID и коду права
         * 
         * @param  int    $ID
         * @param  string $code
         *
         * @return bool
         */
        public function checkRule($ID, $code) {
            return (is_numeric($ID) && is_string($code) && $this->db->count('users_rules', array(
                array(
                    'param' => 'user_id',
                    'value' => $ID
                ),
                array(
                    'param' => 'rule_code',
                    'value' => $code
                )
            )) > 0);
        }

        /**
         * Проверка группы пользователя по его ID и коду группы
         * 
         * @param  int    $ID
         * @param  string $code
         *
         * @return bool
         */
        public function checkGroup($ID, $code) {
            return (is_numeric($ID) && is_string($code) && $this->db->count('users_groups', array(
                array(
                    'param' => 'user_id',
                    'value' => $ID
                ),
                array(
                    'param' => 'group_code',
                    'value' => $code
                )
            )) > 0);
        }

        /**
         * Добавление права пользователю
         * 
         * @param  int|string $ID
         * @param  string     $rule Код права
         *
         * @return bool
         */
        public function addRule($ID, $rule) {
            if(is_numeric($ID) && $this->rules->exists($rule)) {
                return $this->db->insert('users_rules', array(
                    'user_id'   => $ID,
                    'rule_code' => $rule
                ));
            }

            return false;
        }

        /**
         * Добавление группы пользователю
         * 
         * @param  int|string $ID 
         * @param  string     $group Код группы
         *
         * @return bool
         */
        public function addGroup($ID, $group) {
            if(is_numeric($ID) && $this->groups->exists($group)) {
                return $this->db->insert('users_groups', array(
                    'user_id'   => $ID,
                    'group_code' => $group
                ));
            }

            return false;
        }

        /**
         * Добавление права у пользователя
         * 
         * @param  int|string $ID 
         * @param  string     $rule Код права
         *
         * @return bool
         */
        public function removeRule($ID, $rule) {
            if(is_numeric($ID) && is_string($rule)) {
                return $this->db->delete('users_rules', array(
                    array('param' => 'user_id',   'value' => $ID),
                    array('param' => 'rule_code', 'value' => $rule)
                ));
            }

            return false;
        }

        /**
         * Удаление пользователя из группы
         * 
         * @param  int|string $ID
         * @param  string     $group Код группы
         *
         * @return bool
         */
        public function removeGroup($ID, $group) {
            if(is_numeric($ID) && is_string($group)) {
                return $this->db->delete('users_groups', array(
                    array('param' => 'user_id',    'value' => $ID),
                    array('param' => 'group_code', 'value' => $group)
                ));
            }

            return false;
        }

        /**
         * Проверка хеша для пользователя
         *
         * @param  int    $ID
         * @param  string $try
         *
         * @return bool
         */
        public function checkHash($ID, $try) {
            if(is_numeric($ID) && is_string($try) && $try) {
                $res = $this->get(array(
                    'cols'   => 'id',
                    'oneCol' => 0,
                    'oneRow' => 0,
                    'cond'   => array(
                        array(
                            'param' => 'id',
                            'value' => $ID
                        ),
                        array(
                            'param' => 'hash',
                            'value' => $try
                        )
                    )
                ));

                return ($res != null);
            }
            else {
                return false;
            }
        }

        /**
         * Генерация нового хеша для пользователя
         * 
         * @param  int $ID
         *
         * @return string|false
         */
        public function generateHash($ID) {
            $new = \detemiro\random_hash(26, false);

            if($this->updateItem($ID, array(
                'hash' => $new
            ))) {
                return $new;
            }
            else {
                return false;
            }
        }

        protected function __getHandler(array &$res) {
            parent::__getHandler($res);

            if(isset($res['rules']) == false) {
                $res['rules'] = array();
            }
            if(isset($res['groups']) == false) {
                $res['groups'] = array();
            }
        }

        public function __get_rules(&$value) {
            if(is_string($value)) {
                $value = array_unique(explode(',', $value));
            }
        }
        public function __get_groups(&$value) {
            if(is_string($value)) {
                $value = array_unique(explode(',', $value));
            }
        }

        protected function __addBeforeHandler(array &$par) {
            if(parent::__addBeforeHandler($par) === false) {
                return false;
            }
            else {
                if(isset($par['rules'])) {
                    $this->tmpRules = $par['rules'];

                    unset($par['rules']);
                }
                else {
                    $this->tmpRules = null;
                }
                if(isset($par['groups'])) {
                    $this->tmpGroups = $par['groups'];

                    unset($par['groups']);
                }
                else {
                    $this->tmpGroups = null;
                }

                $par['registration'] = date('c');

                return true;
            }
        }
        protected function __addSuccessHandler(array $par) {
            if($this->tmpRules !== null || $this->tmpGroups !== null) {
                $ID = $this->lastInsertId();
            }

            if($this->tmpRules) {
                foreach(\detemiro\take_good_array($this->tmpRules, true) as $rule) {
                    $this->addRule($ID, $rule);
                }

                $this->tmpRules = null;
            }
            if($this->tmpGroups) {
                foreach(\detemiro\take_good_array($this->tmpGroups, true) as $group) {
                    $this->addGroup($ID, $group);
                }

                $this->tmpGroups = null;
            }
        }

        protected function __updateBeforeHandler(array &$par, array $cond = null) {
            if(parent::__updateBeforeHandler($par, $cond) === false) {
                return false;
            }
            else {
                if(array_key_exists('rules', $par)) {
                    if($par['rules']) {
                        $this->tmpRules = $par['rules'];
                    }
                    else {
                        $this->tmpRules = array();
                    }

                    unset($par['rules']);
                }
                else {
                    $this->tmpRules  = null;
                }
                if(array_key_exists('groups', $par)) {
                    if($par['groups']) {
                        $this->tmpGroups = $par['groups'];
                    }
                    else {
                        $this->tmpGroups = array();
                    }

                    unset($par['groups']);
                }
                else {
                    $this->tmpGroups = null;
                }

                return true;
            }
        }
        protected function __updateSuccessHandler(array $par, array $cond = null) {
            if($this->tmpRules !== null || $this->tmpGroups !== null) {
                if($all = $this->getItems(array('cond' => $cond, 'full' => false))) {
                    foreach($all as $user) {
                        if($this->tmpRules !== null) {
                            foreach(\detemiro\take_good_array($this->tmpRules, true) as $rule) {
                                $key = array_search($rule, $user['rules']);
                                if($key === false) {
                                    $this->addRule($user['id'], $rule);
                                }
                                else {
                                    unset($user['rules'][$key]);
                                }
                            }

                            if($user['rules'] && ($args = implode(',', array_pad(array(), count($user['rules']), '?')))) {
                                $this->db->delete('users_rules', array(
                                    array(
                                        'param' => 'user_id',
                                        'value' => $user['id']
                                    ),
                                    array(
                                        'manual'     => "rule_code IN ($args)",
                                        'manualArgs' => $user['rules']
                                    )
                                ));
                            }
                        }
                        if($this->tmpGroups !== null) {
                            foreach(\detemiro\take_good_array($this->tmpGroups, true) as $group) {
                                $key = array_search($group, $user['groups']);
                                if($key === false) {
                                    $this->addGROUP($user['id'], $group);
                                }
                                else {
                                    unset($user['groups'][$key]);
                                }
                            }

                            if($user['groups'] && ($args = implode(',', array_pad(array(), count($user['groups']), '?')))) {
                                $this->db->delete('users_groups', array(
                                    array(
                                        'param' => 'user_id',
                                        'value' => $user['id']
                                    ),
                                    array(
                                        'manual'     => "group_code IN ($args)",
                                        'manualArgs' => $user['groups']
                                    )
                                ));
                            }
                        }
                    }
                }

                $this->tmpRules  = null;
                $this->tmpGroups = null;
            }
        }
    }
?>