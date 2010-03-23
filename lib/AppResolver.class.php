<?php
class AppResolver
{
 public $appPreloadPrivileged = array();
 public $appPreload = array();
 public $defaultApp = 'Default';
 /* @method preload
    @description Preloads applications after setuid.
    @return void
 */
 public function preload()
 {
  foreach ($this->appPreload as $app => $num)
  {
   if (isset(Daemon::$appInstances[$app])) {$num -= sizeof(Daemon::$appInstances[$app]);}
   for ($i = 0; $i < $num; ++$i) {$this->appInstantiate($app);}
  }
 }
  /* @method preloadPrivileged
     @description Preloads applications before setuid.
     @return void
 */
 public function preloadPrivileged()
 {
  foreach ($this->appPreloadPrivileged as $app)
  {
   if (!isset($this->appPreload[$app])) {continue;}
   $num = $this->appPreload[$app];
   if (isset(Daemon::$appInstances[$app])) {$num -= sizeof(Daemon::$appInstances[$app]);}
   for ($i = 0; $i < $num; ++$i) {$this->appInstantiate($app);}
  }
 }
 /* @method getInstanceByAppName
    @param string Application name.
    @description Gets instance of application by it's name.
    @return object AppInstance.
 */
 public function getInstanceByAppName($appName)
 {
  if (!isset(Daemon::$appInstances[$appName])) {return $this->appInstantiate($appName);}
  return Daemon::$appInstances[$appName][array_rand(Daemon::$appInstances[$appName])];
 }
 /* @method getAppPath
    @param string Application name
    @description Gets path to application's PHP-file.
    @return string Path.
 */
 public function getAppPath($app)
 {
  return $this->appDir.$app.'.php';
 }
 /* @method appInstantiate
    @param string Application name
    @description Run new application instance.
    @return object AppInstance.
 */
 public function appInstantiate($app)
 {
  $p = $this->getAppPath($app);
  if (!$p || !is_file($p))
  {
   Daemon::log('appInstantiate('.$app.') failed: application doesn\'t exist.');
   return FALSE;
  }
  if (!isset(Daemon::$appInstances[$app])) {Daemon::$appInstances[$app] = array();}
  $appInstance = include $p;
  if (!is_object($appInstance))
  {
   Daemon::log('appInstantiate('.$app.') failed: catched application is n\'t an object.');
   return FALSE;
  }
  Daemon::$appInstances[$app][] = $appInstance;
  return $appInstance;
 }
 /* @method getRequest
    @param object Request.
    @param object AppInstance of Upstream.
    @param string Default application name.
    @description Routes incoming request to related application.
    @return object Request.
 */
 public function getRequest($req,$upstream,$defaultApp = NULL)
 {
  if (isset($req->attrs->server['APPNAME'])) {$appName = $req->attrs->server['APPNAME'];}
  else {$appName = $defaultApp !== NULL?$defaultApp:$this->defaultApp;}
  $appInstance = $this->getInstanceByAppName($appName);
  if (!$appInstance) {return $req;}
  return $appInstance->handleRequest($req,$upstream);
 }
}