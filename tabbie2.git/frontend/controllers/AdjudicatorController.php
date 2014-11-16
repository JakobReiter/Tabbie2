<?php

namespace frontend\controllers;

use Yii;
use common\models\Adjudicator;
use common\models\search\AdjudicatorSearch;
use frontend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\components\filter\TournamentContextFilter;
use common\models\AdjudicatorInPanel;
use \common\models\Panel;

/**
 * AdjudicatorController implements the CRUD actions for Adjudicator model.
 */
class AdjudicatorController extends BaseController {

    public function behaviors() {
        return [
            'tournamentFilter' => [
                'class' => TournamentContextFilter::className(),
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Adjudicator models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new AdjudicatorSearch(["tournament_id" => $this->_tournament->id]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionReplace() {

        if (Yii::$app->request->isPost) {
            $params = Yii::$app->request->post();
            /* @var $adjInPanel AdjudicatorInPanel */
            $adjInPanel = AdjudicatorInPanel::find()->where([
                        "adjudicator_id" => $params["id"],
                        "panel_id" => $params["old_panel"]
                    ])->one();

            if ($adjInPanel->function == Panel::FUNCTION_CHAIR && $params["pos"] > 0) { //Set away from Chair Position, promote next one
                $newChair = AdjudicatorInPanel::find()->where([
                            "panel_id" => $params["old_panel"]
                        ])->joinWith("adjudicator")->orderBy("strength")->one();
                $newChair->function = Panel::FUNCTION_CHAIR;
                if ($newChair->save())
                    $adjInPanel->function = Panel::FUNCTION_WING;
            }
            if ($params["pos"] == 0) { //is new Chair -> reset old one
                $chair = AdjudicatorInPanel::findOne(['panel_id' => $params["new_panel"], "function" => 1]);
                $chair->function = Panel::FUNCTION_WING;
                if (!$chair->save())
                    return print_r($chair->getErrors(), true);
                else
                    $adjInPanel->function = Panel::FUNCTION_CHAIR;
            }

            $adjInPanel->panel_id = $params["new_panel"];

            return ($adjInPanel->save()) ? 1 : print_r($adjInPanel->getErrors(), true);
        }
        return 0;
    }

    /**
     * Displays a single Adjudicator model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Adjudicator model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new Adjudicator();
        $model->tournament_id = $this->_tournament->id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id, "tournament_id" => $model->tournament_id]);
        } else {
            return $this->render('create', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Adjudicator model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id, "tournament_id" => $model->tournament_id]);
        } else {
            return $this->render('update', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Adjudicator model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Adjudicator model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Adjudicator the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = Adjudicator::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionImport() {
        $tournament = $this->_tournament;
        $model = new \frontend\models\ImportForm();

        if (Yii::$app->request->isPost) {
            $model->scenario = "screen";
            if (Yii::$app->request->post("makeItSo", false)) { //Everything corrected
                $choices = Yii::$app->request->post("field", false);
                $model->tempImport = unserialize(Yii::$app->request->post("csvFile", false));

                //APPLY CHOICES
                foreach ($choices as $row => $choice) {
                    foreach ($choice as $id => $value) {
                        $input = $model->tempImport[$row][$id][0];
                        unset($model->tempImport[$row][$id]);
                        $model->tempImport[$row][$id][0] = $input;
                        $model->tempImport[$row][$id][1]["id"] = $value;
                    }
                }

                //INSERT DATA
                for ($r = 1; $r <= count($model->tempImport); $r++) {
                    $row = $model->tempImport[$r];

                    //Society
                    if (count($row[0]) == 1) { //NEW
                        $society = new \common\models\Society();
                        $society->fullname = $row[0][0];
                        $society->adr = strtoupper(substr($society->fullname, 0, 3));
                        $society->save();
                        $societyID = $society->id;
                    } else if (count($row[0]) == 2) {
                        $societyID = $row[0][1]["id"];
                    }

                    //User
                    if (count($row[1]) == 1) { //NEW
                        $userA = new \common\models\User();
                        $userA->givenname = $row[1][0];
                        $userA->surename = $row[2][0];
                        $userA->username = $userA->givenname . $userA->surename;
                        $userA->email = $row[3][0];
                        $userA->setPassword($userA->email);
                        $userA->generateAuthKey();
                        $userA->time = $userA->last_change = date("Y-m-d H:i:s");
                        if (!$userA->save()) {
                            $inSociety = new \common\models\InSociety();
                            $inSociety->user_id = $userA->id;
                            $inSociety->society_id = $societyID;
                            $inSociety->starting = date("Y-m-d H:i:s");
                            $inSociety->save();
                            Yii::$app->session->addFlash("error", "Save error: " . print_r($userA->getErrors(), true));
                        }
                        $userAID = $userA->id;
                    } else if (count($row[2]) == 2) {
                        $userAID = $row[1][1]["id"];
                    }

                    $adj = new Adjudicator();
                    $adj->user_id = $userAID;
                    $adj->tournament_id = $this->_tournament->id;
                    $adj->strength = $row[4][0];
                    $adj->society_id = $societyID;
                    if (!$adj->save())
                        Yii::$app->session->addFlash("error", "Save error: " . print_r($adj->getErrors(), true));
                }
                return $this->redirect(['index', "tournament_id" => $this->_tournament->id]);
            } else { //FORM UPLOAD
                $file = \yii\web\UploadedFile::getInstance($model, 'csvFile');
                $model->load(Yii::$app->request->post());

                $row = 0;
                if ($file && ($handle = fopen($file->tempName, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

                        if ($row == 0) { //Don't use first column
                            $row++;
                            continue;
                        }

                        if (($num = count($data)) != 5) {
                            throw new \yii\base\Exception("500", "File Syntax Wrong");
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $model->tempImport[$row][$c][0] = trim($data[$c]);
                        }
                        $row++;
                    }
                    fclose($handle);

                    //Find Matches
                    for ($i = 1; $i <= count($model->tempImport); $i++) {
                        //Debating Society
                        $name = $model->tempImport[$i][0][0];
                        $societies = \common\models\Society::find()->where("fullname LIKE '%$name%'")->all();
                        $model->tempImport[$i][0] = array();
                        $model->tempImport[$i][0][0] = $name;
                        $a = 1;
                        foreach ($societies as $s) {
                            $model->tempImport[$i][0][$a] = [
                                "id" => $s->id,
                                "name" => $s->fullname,
                            ];
                            $a++;
                        }

                        //User A
                        $givenname = $model->tempImport[$i][1][0];
                        $surename = $model->tempImport[$i][2][0];
                        $email = $model->tempImport[$i][3][0];
                        $user = \common\models\User::find()->where("(givenname LIKE '%$givenname%' AND surename LIKE '%$surename%') OR surename LIKE '%$email%'")->all();
                        $a = 1;
                        foreach ($user as $u) {
                            $model->tempImport[$i][1][$a] = [
                                "id" => $u->id,
                                "name" => $u->name,
                                "email" => $u->email,
                            ];
                            $a++;
                        }

                        //Just make sure it is int
                        $model->tempImport[$i][4][0] = (int) $model->tempImport[$i][4][0];
                    }
                } else {
                    Yii::$app->session->addFlash("error", "No File available");
                    print_r($file);
                }
            }
        } else
            $model->scenario = "upload";

        return $this->render('import', [
                    "model" => $model,
                    "tournament" => $tournament
        ]);
    }

    public function actionPopup($id, $round_id) {
        $model = $this->findModel($id);
        return $this->renderAjax("_popup", [
                    "model" => $model,
                    "round_id" => $round_id
        ]);
    }

}
