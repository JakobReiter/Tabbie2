<?php

use yii\helpers\Html;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\ResultSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Results');
$tournament = $this->context->_getContext();
$this->params['breadcrumbs'][] = ['label' => $tournament->fullname, 'url' => ['tournament/view', "id" => $tournament->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="result-index">

	<h1><?= Html::encode($this->title) ?></h1>

	<?
	echo ListView::widget([
		'dataProvider' => $rounds,
		'itemView' => function ($model, $key, $index, $widget) {
			return Html::a(Yii::t("app", "Round {number}", ["number" => $model->number]), ['round', "id" => $model->id, "tournament_id" => $model->tournament_id], ['class' => 'btn btn-default btn-block']);
		},
	]);
	?>
</div>
