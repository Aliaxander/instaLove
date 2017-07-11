<?php

namespace app\controllers;

use app\models\CheckTable;
use app\models\Followings;
use app\models\helpers\CheckpointException;
use app\models\Scheduler;
use app\models\Settings;
use app\models\Status;
use app\models\Task;
use app\models\Users;
use InstagramAPI\Instagram;
use Yii;
use yii\base\Exception;
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
        
        return $this->render('index', ['users' => $users, 'status' => Status::getAll()]);
    }
    
    public function actionScheduler($id)
    {
        $scheduler = Scheduler::find()->where(['user' => Yii::$app->request->get('id')])->all();
        
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
            $data->date = Yii::$app->request->post('date');
            $data->task = Yii::$app->request->post('task');
            $data->user = Yii::$app->request->post('user');
            $data->save();
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
        $model = Users::findOne(Yii::$app->request->get('id'));
        if (Yii::$app->request->isPost) {
            $model->userName = Yii::$app->request->post('userName');
            $model->proxy = Yii::$app->request->post('proxy');
            $model->password = Yii::$app->request->post('password');
            $model->email = Yii::$app->request->post('email');
            $model->update();
            
            return $this->redirect('/admin');
        }
        
        return $this->render('edit.twig', ['model' => $model]);
    }
    
    public function actionAddbot()
    {
        $model = new Users();
        if (Yii::$app->request->isPost) {
            $model->userName = Yii::$app->request->post('userName');
            $model->proxy = Yii::$app->request->post('proxy');
            $model->password = Yii::$app->request->post('password');
            $model->email = Yii::$app->request->post('email');
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
