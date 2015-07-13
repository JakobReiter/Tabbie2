<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use kartik\helpers\Html;

//$logoPath = Yii::getAlias("@frontend/assets/images/") . "Tabbie2LogoIcon.png";
//$logo =  Yii::$app->assetManager->publish($logoPath)[1];
$logo = "data:image/png;base64,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";

NavBar::begin([
	'brandLabel' => Html::tag("img", "", ["src" => $logo, "alt" => Yii::$app->params["appName"] . " Logo"]) . "&nbsp;" . Yii::$app->params["appName"],
	'brandUrl' => Yii::$app->homeUrl,
	'options' => [
		'class' => 'navbar-inverse navbar-fixed-top',
	],
]);
$menuItems = [
	['label' => Yii::t("app", 'Home'), 'url' => ['/site/index']],
	['label' => Yii::t("app", 'About'), 'url' => ['/site/about']],
	['label' => Yii::t("app", 'How-To'), 'url' => ['/site/how-to']],
	['label' => Html::icon("calendar") . "&nbsp;" . Yii::t("app", 'Tournaments'), 'url' => ['tournament/index']],
];

if (Yii::$app->user->isAdmin()) {
	$menuItems[] = ['label' => Html::icon("globe") . "&nbsp;" . Yii::t("app", 'Users'), 'url' => ['user/index']];
}

if (Yii::$app->user->isGuest) {
	$menuItems[] = ['label' => Html::icon("pencil") . "&nbsp;" . Yii::t("app", 'Signup'), 'url' => ['/site/signup']];
	$menuItems[] = ['label' => Html::icon("log-in") . "&nbsp;" . Yii::t("app", 'Login'), 'url' => ['/site/login']];
}
else {
	$menuItems[] = [
		'label' => Html::icon("user") . "&nbsp;" . Yii::t("app", "{user}'s Profile", ["user" => Yii::$app->user->getModel()->givenname]),
		'url' => ['user/view', 'id' => Yii::$app->user->id],
	];
	$menuItems[] = [
		'label' => Html::icon("tags") . "&nbsp;" . Yii::t("app", "{user}'s History", ["user" => Yii::$app->user->getModel()->givenname]),
		'url' => ['history/index', 'user_id' => Yii::$app->user->id],
	];
	$menuItems[] = [
		'label' => Html::icon("log-out") . "&nbsp;" . Yii::t("app", 'Logout'),
		'url' => ['/site/logout'],
		'linkOptions' => ['data-method' => 'post']
	];
}
echo Nav::widget([
	'options' => ['class' => 'navbar-nav menu navbar-right'],
	'items' => $menuItems,
	'encodeLabels' => false,
]);
NavBar::end();