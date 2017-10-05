<?php

namespace app\controllers;

use app\models\CheckTable;
use app\models\Followers;
use app\models\Followings;
use app\models\ForLikes;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Status;
use app\models\Task;
use app\models\Users;
use GuzzleHttp\Client;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class AdminController
 *
 * @package app\controllers
 */
class AdminController extends Controller
{
    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        
        return parent::beforeAction($action);
    }
    
    /**
     * @return string|Response
     */
    public function actionIndex()
    {
        $users = Users::find()->all();
        foreach ($users as $user) {
            $progress = '';
            $progress1 = 0;
            $progress2 = 0;
            $progressBar[$user->id] = '';
            $taskIdAll[$user->id] = $user->task;
            //Статистика процесса лайкинга:
            if ($user->task === 5 || $user->task === 7 || $user->task === 9 || $user->task === 13) {
                $progress1 = ForLikes::find()->where('userId=:user and (status=1 or status=0)',
                    [':user' => $user->id])->count('id');
                $progress2 = ForLikes::find()->where('userId=:user and status=1',
                    [':user' => $user->id])->count('id');
            }
            //статистика процесса фолловинга
            if ($user->task === 3 || $user->task === 11) {
                $progress1 = Followings::find()->where('userId=:user and status=1',
                    [':user' => $user->id])->count('id');
                $progress2 = Followings::find()->where('userId=:user and status=1 and isComplete=1',
                    [':user' => $user->id])->count('id');
            }
            if (isset($progress1)) {
                $procent = $progress1 / 100;
                if ($procent != 0) {
                    $progress = round($progress2 / $procent);
                    $progressBar[$user->id] = $progress;
                    $progress .= "%";
                }
            }
    
            $progressAll[$user->id] = $progress;
            if (!empty($progress)) {
                $progressAll[$user->id] = "$progress2/$progress1";
            }
            $followers = Followers::find()->where(['userId' => $user->id])->count("id");
            $followersAll[$user->id] = $followers;
            
            if ($user->task == 1) {
                $scheduler = Scheduler::find()->where([
                    'user' => $user->id,
                    'status' => 0
                ])->orderBy(['date' => 'desc'])->one();
                if (count($scheduler) == 1) {
                    $user->task = Task::findIdentity($scheduler->task);
                }
            }
            if (is_int($user->task)) {
                $user->task = Task::findIdentity($user->task);
            }
        }
    
        return $this->render('index', [
            'users' => $users,
            'status' => Status::getAll(),
            'progress' => $progressAll,
            'followers' => $followersAll,
            'taskIdAll' => $taskIdAll,
            'progressBar' => $progressBar
        ]);
    }
    
    public function actionStatsScheduler()
    {
        $stats = ForLikes::findAll(['scheduler' => Yii::$app->request->get('id')]);
        
        return $this->render('stats.twig', ['stats' => $stats]);
    }
    
    public function actionReloadTask()
    {
        $stats = Users::findOne(['id' => Yii::$app->request->get('id')]);
        $stats->task = $stats->task - 1;
        $stats->update();
        
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionScheduler($id)
    {
        $scheduler = Scheduler::find()->where(['user' => Yii::$app->request->get('id')])->orderBy('date desc')->all();
        return $this->render('scheduler.twig',
            ['schedulers' => $scheduler, 'tasks' => Task::getAll(), 'id' => Yii::$app->request->get('id')]);
    }
    
    public function actionDelscheduler()
    {
        $model = Scheduler::findOne(Yii::$app->request->get('id'));
        $model->delete();
        
        return $this->redirect(Yii::$app->request->referrer);
    }
    
    public function actionAjaxAddScheduler()
    {
        if (Yii::$app->request->isPost) {
            $data = new Scheduler();
            // echo Yii::$app->request->post('date');
    
            $myDateTime = \DateTime::createFromFormat('d-m-Y H:i', Yii::$app->request->post('date'));
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            $data->date = $newDateString;
    
            $myDateTime = \DateTime::createFromFormat('d-m-Y H:i', Yii::$app->request->post('date'));
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            $data->dateTo = $newDateString;
            
            $data->task = Yii::$app->request->post('task');
            $data->user = Yii::$app->request->post('user');
            $data->account = Yii::$app->request->post('account');
            $data->save();
        }
        
        return @$data->id;
    }
    
    public function actionAjaxUpdateScheduler()
    {
        if (Yii::$app->request->isPost) {
            $data = Scheduler::find()->where(['id' => Yii::$app->request->post('id')])->one();
            $myDateTime = \DateTime::createFromFormat('d-m-Y H:i', Yii::$app->request->post('date'));
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            $data->date = $newDateString;
            $data->update();
        }
        
        return @$data->id;
    }
    
    public function actionSettings()
    {
    
        if (Yii::$app->request->isPost) {
            foreach (Yii::$app->request->post() as $key => $val) {
                $data = Settings::findOne($key);
                $data->value = $val;
                $data->update();
            }
        }
        $settings = Settings::find()->all();
        
        return $this->render('settings.twig', ['settings' => $settings]);
    }
    
    public function actionAjaxfollow()
    {
        $model = Followings::findOne(Yii::$app->request->get('id'));
        $model->status = Yii::$app->request->get('status');
        
        return $model->update();
    }
    
    public function actionStart()
    {
        $model = Users::findOne(Yii::$app->request->get('id'));
        $model->task = Yii::$app->request->get('task');
        $model->update();
        
        return $this->redirect("/admin");
    }
    
    public function actionCheck()
    {
        $countCheck = count(CheckTable::find()->where('status=0 and user=:user',
            [':user' => Yii::$app->request->get('id')])->all());
        $model = Followings::findAll(['userId' => Yii::$app->request->get('id')]);
        
        if (count($model) === 0 && empty(Yii::$app->request->get('check')) and $countCheck == 0) {
            return $this->redirect('/admin/recheck/?id=' . Yii::$app->request->get('id'));
        }
        
        
        return $this->render('follow.twig', [
            'users' => $model,
            'id' => Yii::$app->request->get('id'),
            'countCheck' => $countCheck
        ]);
    }
    
    
    public function actionRecheck()
    {
        $task = new CheckTable();
        $task->status = 0;
        $task->user = Yii::$app->request->get('id');
        $task->save();
        
        return $this->redirect("/admin/check/?heck=true&id=" . Yii::$app->request->get('id'));
    }
    
    public function actionDel()
    {
        $model = Users::findOne(Yii::$app->request->get('id'));
        $model->delete();
        
        return $this->redirect('/admin');
    }
    
    public function actionEdit()
    {
        $error = false;
        $model = Users::findOne(Yii::$app->request->get('id'));
        if (Yii::$app->request->isPost) {
            $speed = 0;
            if (!empty(Yii::$app->request->post('proxy'))) {
                try {
                    $client = new Client([
                        'base_uri' => 'https://instagram.com/',
                        'timeout' => 4,
                        'proxy' => Yii::$app->request->post('proxy'),
                    ]);
                    $one = microtime();
            
                    $result = $client->request('get', '/');
                    var_dump($result->getStatusCode());
                    $two = microtime();
                    $speed = round($two - $one);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $speed = '>4000';
                }
            }
            $model->proxySpeed = $speed;
            $model->userName = Yii::$app->request->post('userName');
            $model->proxy = Yii::$app->request->post('proxy');
            $model->password = Yii::$app->request->post('password');
            $model->email = Yii::$app->request->post('email');
            $model->timeoutMin = Yii::$app->request->post('timeoutMin');
            $model->timeoutMax = Yii::$app->request->post('timeoutMax');
            $model->maxLikes = Yii::$app->request->post('maxLikes');
            $model->likeTimeoutMin = Yii::$app->request->post('likeTimeoutMin');
            $model->likeTimeoutMax = Yii::$app->request->post('likeTimeoutMax');
            $model->update();
            if (!$error) {
                return $this->redirect('/admin');
            }
        }
    
        return $this->render('edit.twig', ['model' => $model, 'error' => $error]);
    }
    
    public function actionAddbot()
    {
        $model = new Users();
        if (Yii::$app->request->isPost) {
            $speed = 0;
            if (!empty(Yii::$app->request->post('proxy'))) {
                try {
                    $client = new Client([
                        'base_uri' => 'https://instagram.com/',
                        'timeout' => 4,
                        'proxy' => Yii::$app->request->post('proxy'),
                    ]);
                    $one = microtime();
            
                    $result = $client->request('get', '/');
                    var_dump($result->getStatusCode());
                    $two = microtime();
                    $speed = round($two - $one);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $speed = '>4000';
                }
            }
            $model->proxySpeed = $speed;
            $model->userName = Yii::$app->request->post('userName');
            $model->proxy = Yii::$app->request->post('proxy');
            $model->password = Yii::$app->request->post('password');
            $model->email = Yii::$app->request->post('email');
            $model->timeoutMin = Yii::$app->request->post('timeoutMin');
            $model->timeoutMax = Yii::$app->request->post('timeoutMax');
            $model->maxLikes = Yii::$app->request->post('maxLikes');
            $model->likeTimeoutMin = Yii::$app->request->post('likeTimeoutMin');
            $model->likeTimeoutMax = Yii::$app->request->post('likeTimeoutMax');
            $model->save();
            
            return $this->redirect('/admin');
        }
        
        return $this->render('edit.twig');
    }
    
    /**
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        
        return $this->goHome();
    }
}
