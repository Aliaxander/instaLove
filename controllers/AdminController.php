<?php

namespace app\controllers;

use app\models\Followings;
use app\models\helpers\CheckpointException;
use app\models\Settings;
use app\models\Status;
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
    /**
     * @return string|Response
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        $users = Users::find()->all();
        
        return $this->render('index', ['users' => $users, 'status' => Status::getAll()]);
    }
    
    public function actionSettings()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        
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
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        
        $model = Followings::findAll(['userId' => Yii::$app->request->get('id')]);
        
        if (count($model) === 0 && empty(Yii::$app->request->get('check'))) {
            return $this->redirect('/admin/recheck/?id=' . Yii::$app->request->get('id'));
        }
        
        
        return $this->render('follow.twig', ['users' => $model, 'id' => Yii::$app->request->get('id')]);
    }
    
    
    public function actionRecheck()
    {
        $user = Users::findOne(Yii::$app->request->get('id'));
        
        $instaApi = new Instagram(false, false, [
            'storage' => 'mysql',
            'dbhost' => '164.132.168.121',
            'dbname' => 'instaFollow',
            'dbusername' => 'instaFollow',
            'dbpassword' => 'instaFollow',
        ]);
        if (!empty($user->proxy)) {
            $instaApi->setProxy($user->proxy);
        }
        $instaApi->setUser($user->userName, $user->password);
        if (!$instaApi->isLoggedIn) {
            try {
                $instaApi->login(true);
            } catch (\Exception $error) {
                new CheckpointException($user, $error->getMessage());
            }
        }
        $result = $instaApi->getSelfUsersFollowing();
        //print_r($result->users);
        
        foreach ($result->users as $user) {
            $model = new Followings();
            $model->token = Yii::$app->request->get('id') . '_' . $user->pk;
            $model->userId = Yii::$app->request->get('id');
            $model->followId = $user->pk;
            $model->profile_pic_url = $user->profile_pic_url;
            $model->username = $user->username;
            $model->full_name = $user->full_name;
            try {
                $model->save();
            } catch (Exception $e) {
                $model->isNewRecord = false;
                $model->save();
            }
        }
        
        return $this->redirect("/admin/check/?heck=true&id=" . Yii::$app->request->get('id'));
    }
    
    public function actionDel()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        $model = Users::findOne(Yii::$app->request->get('id'));
        $model->delete();
        
        return $this->redirect('/admin');
    }
    
    public function actionEdit()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        
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
        if (Yii::$app->user->isGuest) {
            return $this->redirect("/");
        }
        
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
