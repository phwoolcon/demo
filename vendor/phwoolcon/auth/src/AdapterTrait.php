<?php

namespace Phwoolcon\Auth;

use Phalcon\Di;
use Phalcon\Security;
use Phwoolcon\Auth\Adapter\Exception;
use Phwoolcon\Cache;
use Phwoolcon\Cookies;
use Phwoolcon\Log;
use Phwoolcon\Model\User;
use Phwoolcon\Session;
use Phwoolcon\Text;

trait AdapterTrait
{
    /**
     * @var Di
     */
    protected $di;
    /**
     * @var Security
     */
    protected $hasher;
    protected $options = [];
    protected $sessionKey;
    protected $userModel;
    protected $uidKey;
    /**
     * @var User
     */
    protected $user;

    public function __construct(array $options, $hasher, Di $di)
    {
        $this->hasher = $hasher;
        $this->options = $options;
        $this->sessionKey = $options['session_key'];
        $this->uidKey = $options['uid_key'];
        $this->di = $di;
        $this->setUserModel($options['user_model']);
    }

    public function activatePendingConfirmationUser($confirmationCode)
    {
        /* @var User $userModel */
        $userModel = $this->userModel;
        if (!($userData = $this->getPendingConfirmationData()) ||
            !($uid = fnGet($userData, $this->uidKey)) ||
            ($confirmationCode != fnGet($userData, 'confirmation_code')) ||
            $userModel::findFirstSimple([$this->uidKey => $uid])
        ) {
            return false;
        }
        /* @var User $user */
        $user = new $this->userModel;
        $user->setData($userData)
            ->setData('confirmed', true);
        if (!$user->save()) {
            Log::error('User activation failed on save: ' . var_export($user->getStringMessages(), true));
            return false;
        }
        $this->removePendingConfirmationData();
        $this->setUserAsLoggedIn($user);
        return $user;
    }

    public function changePassword($password, $originPassword = null)
    {}

    protected function checkRegisterCredential(array $credential)
    {
        if (empty($credential['login']) || empty($credential['password'])) {
            throw new Exception(__($this->options['hints']['invalid_user_credential']));
        }
        if ($this->findUser($credential)) {
            throw new Exception(__($this->options['hints']['user_credential_registered']));
        }
    }

    /**
     * @param array $credential
     * @param bool  $confirmed
     * @return User|false
     */
    abstract public function createUser(array $credential, $confirmed = null);

    /**
     * @param array $credential
     * @return User|false
     */
    abstract public function findUser(array $credential);

    public function forgotPassword(array $credential)
    {}

    public function getOption($key)
    {
        return fnGet($this->options, $key);
    }

    public function getPendingConfirmationData()
    {
        return ($key = Session::get('pending-confirm')) ? Cache::get('reg-pc-' . $key) : null;
    }

    public function getRememberTokenFromCookie()
    {
        return Cookies::get($this->options['remember_login']['cookie_key'])->useEncryption(false)->getValue();
    }

    /**
     * @return User|false Current logged in user
     */
    public function getUser()
    {
        if ($this->user !== null) {
            return $this->user;
        }
        $rememberToken = false;
        if (!$uid = Session::get($this->sessionKey . '.uid')) {
            if ($rememberToken = $this->getRememberTokenFromCookie()) {
                $uid = substr($rememberToken, 32);
            } else {
                return $this->user = false;
            }
        }
        /* @var User $userModel */
        $userModel = $this->userModel;
        if (!$this->user = $userModel::findFirstSimple([$this->uidKey => $uid])) {
            Session::clear();
        }
        if ($this->user && $rememberToken) {
            if (!method_exists($this->user, 'getRememberToken') || $this->user->getRememberToken() != $rememberToken) {
                $this->user = null;
                Session::clear();
            } else {
                $this->setUserAsLoggedIn($this->user);
            }
        }
        return $this->user;
    }

    public function login(array $credential)
    {
        if (empty($credential['login']) || empty($credential['password'])) {
            throw new Exception(__($this->options['hints']['invalid_user_credential']));
        }
        if (!$user = $this->findUser($credential)) {
            throw new Exception(__($this->options['hints']['invalid_user_credential']));
        }
        if (!$this->hasher->checkHash($credential['password'], $user->getData($this->options['user_fields']['password_field']))) {
            throw new Exception(__($this->options['hints']['invalid_password']));
        }
        if (!empty($credential['remember']) && method_exists($user, 'setRememberToken')) {
            $rememberToken = Text::token() . $user->getId();
            $user->setRememberToken($rememberToken);
            Cookies::set($cookieName = $this->options['remember_login']['cookie_key'], $rememberToken,
                time() + $this->options['remember_login']['ttl'], null, null, null, true
            );
            Cookies::get($cookieName)->useEncryption(false);
        }
        $this->setUserAsLoggedIn($user);
        return $user;
    }

    public function logout()
    {
        if ($this->getUser() && method_exists($this->user, 'removeRememberToken')) {
            $this->user->removeRememberToken();
        }
        Cookies::set($cookieName = $this->options['remember_login']['cookie_key'], '', null, null, null, null, true);
        Cookies::get($cookieName)->useEncryption(false);
        $this->user = null;
        Session::clear();
        return $this;
    }

    /**
     * @param User  $user
     * @param array $credential
     * @return $this
     */
    public function pushPendingConfirmation($user, array $credential)
    {
        $user->setData('login', $login = $credential['login'])
            ->setData('confirm_class', get_called_class());
        $data = $user->getData();
        Session::set('pending-confirm', $key = md5($login));
        Cache::set('reg-pc-' . $key, $data, $this->options['register']['confirmation_code_ttl']);
        return $this;
    }

    public function register(array $credential, $confirmed = null, $role = null)
    {
        $this->checkRegisterCredential($credential);
        $user = $this->createUser($credential, $confirmed);
        if ($user->getData('confirmed')) {
            $this->setUserAsLoggedIn($user);
        }
        return $user;
    }

    public function removePendingConfirmationData()
    {
        Cache::delete('reg-pc-' . Session::get('pending-confirm'));
        Session::remove('pending-confirm');
        return $this;
    }

    public function reset()
    {
        $this->user = null;
    }

    public function setDi(Di $di)
    {
        $this->di = $di;
    }

    public function setUserModel($class)
    {
        $this->userModel = $this->di->has($class) ? $this->di->getRaw($class) : $class;
        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUserAsLoggedIn($user)
    {
        $this->user = $user;
        $user and Session::set($this->sessionKey . '.uid', $user->getId());
        return $this;
    }
}
