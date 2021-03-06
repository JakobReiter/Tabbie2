<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "adjudicator".
 *
 * @property integer $id
 * @property integer $tournament_id
 * @property integer $active
 * @property integer $user_id
 * @property integer $strength 0-99
 * @property integer $society_id
 * @property integer $can_chair
 * @property integer $are_watched
 * @property integer $checkedin
 * @property Tournament $tournament
 * @property User $user
 * @property Society $society
 * @property Society[] $societies
 * @property AdjudicatorInPanel[] $adjudicatorInPanels
 * @property Panel[] $panels
 * @property Team[] $teams
 */
class Adjudicator extends \yii\db\ActiveRecord
{


    const MAX_RATING = 99;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'adjudicator';
    }

    /**
     * @inheritdoc
     * @return VTAQuery
     */
    public static function find()
    {
        return new VTAQuery(get_called_class());
    }

    public static function getCSSStrength($id = null)
    {
        return "st" . intval($id / 10);
    }

    /**
     * Sort comparison function based on strength
     *
     * @param Adjudicator $a
     * @param Adjudicator $b
     *
     * @return boolean
     */
    public static function compare_strength($a, $b)
    {
        $as = $a["strength"];
        $bs = $b["strength"];

        return ($as < $bs) ? 1 : (($as > $bs) ? -1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tournament_id', 'user_id', 'society_id'], 'required'],
            [['tournament_id', 'active', 'user_id', 'strength', 'can_chair', 'are_watched', 'society_id', 'breaking'], 'integer'],
            ['strength', 'integer', 'max' => self::MAX_RATING, 'min' => 0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'Adjudicator') . ' ' . Yii::t('app', 'ID'),
            'tournament_id' => Yii::t('app', 'Tournament') . ' ' . Yii::t('app', 'ID'),
            'active' => Yii::t('app', 'Active'),
            'user_id' => Yii::t('app', 'User') . ' ' . Yii::t('app', 'ID'),
            'strength' => Yii::t('app', 'Strength'),
            'societyName' => Yii::t('app', 'Society') . ' ' . Yii::t('app', 'Name'),
            'can_chair' => Yii::t('app', 'Can Chair'),
            'are_watched' => Yii::t('app', 'Are Watched'),
            'society_id' => Yii::t('app', 'Society'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields["name"] = function () {
            return $this->getName();
        };

        return $fields;
    }

    /**
     * Get the Name of the Adjudicator.
     *
     * If a panel_id is provided then the corresponding function will be added to the name like:
     * Name (t) ... for Trainees
     *
     * @param null|integer $panel_id
     * @return string
     */
    public function getName($panel_id = null)
    {
        $key = "AdjudicatorName#" . $this->id . "#" . $panel_id;

        if (Yii::$app->cache->exists($key)) {
            return Yii::$app->cache->get($key);
        }

        $name = $this->user->name;

        /** @var AdjudicatorInPanel $aIp */
        $aIp = AdjudicatorInPanel::find()->where(["adjudicator_id" => $this->id, "panel_id" => $panel_id])->one();
        if ($aIp instanceof AdjudicatorInPanel) {
            switch ($aIp->function) {
                case Panel::FUNCTION_TRAINEE:
                    $name .= (" " . Panel::INDICATOR_TRAINEE);
                    break;
            }
        }

        Yii::$app->cache->set($key, $name, 1 * 60 * 60);

        return $name;
    }

    /**
     * Get the Gender of the Adjudicator.
     *
     * @return string
     */
    public function getGender($panel_id = null)
    {
        $key = "AdjudicatorGender#" . $this->id . "#" . $panel_id;

        if (Yii::$app->cache->exists($key)) {
            return Yii::$app->cache->get($key);
        }

        $gender = $this->user->gender;

        Yii::$app->cache->set($key, $gender, 1 * 60 * 60);

        return $gender;
    }

    /**
     * Get the ESL Status of the Adjudicator.
     *
     * @return string
     */
    public function getLanguage($panel_id = null)
    {
        $key = "AdjudicatorESL#" . $this->id . "#" . $panel_id;

        if (Yii::$app->cache->exists($key)) {
            return Yii::$app->cache->get($key);
        }

        $language_status = $this->user->language_status;

        Yii::$app->cache->set($key, $language_status, 1 * 60 * 60);

        return $language_status;
    }

    public function getSocietyName()
    {
        return $this->society->fullname;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTournament()
    {
        return $this->hasOne(Tournament::className(), ['id' => 'tournament_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdjudicatorInPanels()
    {
        return $this->hasMany(AdjudicatorInPanel::className(), ['adjudicator_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrikedTeams()
    {
        return $this->hasMany(Team::className(), ['id' => 'team_id'])
            ->viaTable('team_strike', ['adjudicator_id' => 'id'], function ($query) {
                $query->onCondition(['accepted' => 1]);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrikedAdjudicators()
    {
        return $this->hasMany(Adjudicator::className(), ['id' => 'adjudicator_from_id'])
            ->viaTable('adjudicator_strike', ['adjudicator_to_id' => 'id'], function ($query) {
                $query->onCondition(['accepted' => 1]);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPanels()
    {
        return $this->hasMany(Panel::className(), ['id' => 'panel_id'])
            ->viaTable('adjudicator_in_panel', ['adjudicator_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInSocieties()
    {
        return $this->hasMany(InSociety::className(), ['user_id' => 'user_id']);
    }

    /**
     * Gets the main society the user registered with.
     * DO NOT use for same society clash since user can have multiple active societies see
     * [[Adjudicator::getSocieties()]]
     * @return \yii\db\ActiveQuery
     */
    public function getSociety()
    {
        return $this->hasOne(Society::className(), ['id' => 'society_id']);
    }

    /**
     * Gets all current Societies the user is in.
     *
     * @param bool $elasticMembership Virtually prolongs the membership to a Society by the config parameter
     *                                "time_to_still_consider_active_in_society".
     *
     * @return $this
     */
    public function getSocieties($elasticMembership = false)
    {
        $query = Society::find()->leftJoin("in_society", "id = society_id")->andWhere(["user_id" => $this->user_id]);

        if ($elasticMembership)
            return $query->andWhere("(ending IS NULL OR ending > DATE_ADD(ending, INTERVAL -" . Yii::$app->params["time_to_still_consider_active_in_society"] . "))");
        else
            return $query->andWhere("(ending IS NULL)");
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChair()
    {
        return $this->can_chair;
    }

    public function getStrengthOutput()
    {
        return Adjudicator::getStrengthLabel($this->strength) . " (" . $this->strength . ")";
    }

    /**
     * @param int $strength
     *
     * @return array|string
     */
    public static function getStrengthLabel($strength = null)
    {
        if ($strength !== null) {
            if ($strength === 0) {
                return Yii::t("app", 'Not Rated');
            }
            $strength = intval($strength / 10);
        }

        $table = [
            0 => Yii::t('app', 'Punished Adjudicator'),
            1 => Yii::t("app", 'Bad Adjudicator'),
            2 => Yii::t("app", 'Can Judge'),
            3 => Yii::t("app", 'Decent Adjudicator'),
            4 => Yii::t("app", 'Average Adjudicator'),
            5 => Yii::t("app", 'High Potential'),
            6 => Yii::t("app", 'Average Chair'),
            7 => Yii::t("app", 'Good Chair'),
            8 => Yii::t("app", 'Breaking Chair'),
            9 => Yii::t("app", 'Chief Adjudicator'),
        ];

        return (isset($table[$strength])) ? $table[$strength] : $table;
    }

    public function getPastAdjudicatorIDs($exclude_current = false)
    {
        //Works without tournament_id because adjudicator is only valid in tournament scope

        if ($exclude_current) {
            $sql = "SELECT a.adjudicator_id AS aid, b.adjudicator_id AS bid, a.panel_id AS pid, c.round_id
				FROM adjudicator_in_panel AS a
				LEFT JOIN adjudicator_in_panel AS b ON a.panel_id = b.panel_id
				LEFT JOIN panel AS p ON a.panel_id = p.id
				LEFT JOIN debate AS c ON p.id = c.panel_id
				WHERE a.adjudicator_id != b.adjudicator_id AND a.adjudicator_id = " . $this->id . "
				GROUP BY bid
				HAVING c.round_id != " . $exclude_current . ";";

        } else {
            $sql = "SELECT a.adjudicator_id AS aid, b.adjudicator_id AS bid, a.panel_id AS pid
				FROM adjudicator_in_panel AS a
				LEFT JOIN adjudicator_in_panel AS b ON a.panel_id = b.panel_id
				LEFT JOIN panel AS p ON a.panel_id = p.id
				LEFT JOIN debate AS c ON p.id = c.panel_id
				WHERE a.adjudicator_id != b.adjudicator_id AND a.adjudicator_id = " . $this->id . "
				GROUP BY bid";
        }

        $model = \Yii::$app->db->createCommand($sql);
        $past = $model->queryAll();

        return ArrayHelper::getColumn($past, "bid");
    }

    public function getPastAdjudicatorIDsWithRoundNumbers($current_round_id)
    {
        $sql = "SELECT b.adjudicator_id AS bid, r.label
			FROM adjudicator_in_panel AS a
			LEFT JOIN adjudicator_in_panel AS b ON a.panel_id = b.panel_id
			LEFT JOIN panel AS p ON a.panel_id = p.id
			LEFT JOIN debate AS c ON p.id = c.panel_id
			LEFT JOIN round AS r ON r.id = c.round_id
			WHERE a.adjudicator_id != b.adjudicator_id
			AND a.adjudicator_id = " . $this->id . "
			AND r.label < " . $current_round_id . ";";

        $model = \Yii::$app->db->createCommand($sql);
        $past = $model->queryAll();
        return $past;
    }

    public function getPastTeamIDs($exlude_round_id = false)
    {

        if (is_int($exlude_round_id)) {
            $sql = "SELECT og_team_id, oo_team_id, cg_team_id, co_team_id, round_id FROM adjudicator_in_panel AS aip LEFT JOIN panel ON panel.id = aip.panel_id RIGHT JOIN debate ON debate.panel_id = panel.id WHERE adjudicator_id = " . $this->id . " AND round_id < " . $exlude_round_id;
        } else {
            $sql = "SELECT og_team_id, oo_team_id, cg_team_id, co_team_id FROM adjudicator_in_panel AS aip LEFT JOIN panel ON panel.id = aip.panel_id RIGHT JOIN debate ON debate.panel_id = panel.id WHERE adjudicator_id = " . $this->id;

        }

        $model = \Yii::$app->db->createCommand($sql);
        $queryresult = $model->queryAll();
        $pastIDs = [];
        foreach ($queryresult as $line) {
            $pastIDs[] = $line["og_team_id"];
            $pastIDs[] = $line["oo_team_id"];
            $pastIDs[] = $line["cg_team_id"];
            $pastIDs[] = $line["co_team_id"];
        }

        return $pastIDs;
    }

    public function getPastTeamIDsWithRoundNumbers($current_round)
    {

        $sql = "SELECT og_team_id, oo_team_id, cg_team_id, co_team_id, round.label
			 FROM adjudicator_in_panel AS aip
			 LEFT JOIN panel ON panel.id = aip.panel_id
			 RIGHT JOIN debate ON debate.panel_id = panel.id
			 LEFT JOIN round ON debate.round_id = round.id
			 WHERE adjudicator_id = " . $this->id . "
			 AND round.label < " . $current_round;

        $model = \Yii::$app->db->createCommand($sql);
        $queryresult = $model->queryAll();
        return $queryresult;
    }

    public function beforeDelete()
    {
        $panelCount = AdjudicatorInPanel::find()->where(["adjudicator_id" => $this->id])->count();
        if (parent::beforeDelete()) {
            if ($panelCount == 0)
                return true;
            else {
                Yii::$app->session->addFlash("error", Yii::t("app", "Can't delete Adjudicator {name} because he/she is already in use", [
                    "name" => $this->getName()
                ]));
            }
        }

        return false;
    }

}
