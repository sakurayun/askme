<?php
  define('ASK_BASEDIR', dirname(__FILE__));
  define('ASK_CSRF_TOKEN', 'askme_csrf_token');
  require_once ASK_BASEDIR . '/config.php';
  require_once ASK_BASEDIR . '/classes/AskMeDB.php';
  require_once ASK_BASEDIR . '/classes/MoParser.php';

  defined('IS_ASK') || die('Direct access not allowed.');

  if (empty(AskMeConfig::$admin_password) || empty(AskMeConfig::$site_secret)) {
    die('No admin password or site secret given! Edit your config.php.');
  }

  if (!isset($_COOKIE[ASK_CSRF_TOKEN])) {
    setcookie(ASK_CSRF_TOKEN, uniqid(true));
  }

  header('X-Frame-Options: deny');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
  header("Feature-Policy: document-domain 'none', sync-xhr 'none'");
  header('X-XSS-Protection: 1; mode=block');
  header('Cache-Control: no-cache');

  AskMeDB::$instance = new AskMeDB();

  function is_admin() {
    if (!isset($_COOKIE['askme_token']) || !isset($_COOKIE['askme_token_expire'])) {
      return false;
    }

    $token = $_COOKIE['askme_token'];
    $expire_at = $_COOKIE['askme_token_expire'];
    if ($expire_at - time() < 0) {
      return false;
    }

    $ok = get_token($expire_at);

    return hash_equals($ok, $token);
  }

  function login($password) {
    if (hash_equals(AskMeConfig::$admin_password, $password)) {
      $expire_at = time() + 30 * 24 * 60 * 60;
      setcookie('askme_token', get_token($expire_at), $expire_at);
      setcookie('askme_token_expire', $expire_at, $expire_at);
    }
  }

  function logout() {
    setcookie('askme_token', '', 0);
    setcookie('askme_token_expire', '', 0);
  }

  function get_token($expire_at) {
    return sha1(AskMeConfig::$site_secret . $expire_at . AskMeConfig::$admin_password);
  }

  function __($message) {
    static $lang;
    $locale = AskMeConfig::$site_locale;
    if ($lang === NULL) {
      $lang = new MoParser();
      $lang->loadTranslationData('locales/'. $locale . '.mo', $locale . '');
    }
    return $lang->translate($locale, $message);
  }

  function static_url($filename) {
    return $filename . '?v=' . filemtime($filename);
  }
