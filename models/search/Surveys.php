<?php
/**
 * Surveys
 *
 * Surveys represents the model behind the search form about `dpadjogja\survey\models\Surveys`.
 *
 * @author Putra Sudaryanto <putra@ommu.co>
 * @contact (+62)856-299-4114
 * @copyright Copyright (c) 2019 OMMU (www.ommu.co)
 * @created date 4 December 2019, 01:58 WIB
 * @link https://github.com/ommu/dpadjogja-survey
 *
 */

namespace dpadjogja\survey\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use dpadjogja\survey\models\Surveys as SurveysModel;

class Surveys extends SurveysModel
{
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id', 'publish', 'respondent_id', 'service_id', 'creation_id', 'modified_id'], 'integer'],
			[['creation_date', 'modified_date', 'updated_date', 'gender', 'educationId', 'workId', 'serviceName', 'creationDisplayname', 'modifiedDisplayname'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function scenarios()
	{
		// bypass scenarios() implementation in the parent class
		return Model::scenarios();
	}

	/**
	 * Tambahkan fungsi beforeValidate ini pada model search untuk menumpuk validasi pd model induk. 
	 * dan "jangan" tambahkan parent::beforeValidate, cukup "return true" saja.
	 * maka validasi yg akan dipakai hanya pd model ini, semua script yg ditaruh di beforeValidate pada model induk
	 * tidak akan dijalankan.
	 */
	public function beforeValidate() {
		return true;
	}

	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ActiveDataProvider
	 */
	public function search($params, $column=null)
	{
		if(!($column && is_array($column)))
			$query = SurveysModel::find()->alias('t');
		else
			$query = SurveysModel::find()->alias('t')->select($column);
		$query->joinWith([
			// 'respondent.education respondent', 
			// 'service service', 
			// 'creation creation', 
			// 'modified modified'
		]);
		if((isset($params['sort']) && in_array($params['sort'], ['gender', '-gender', 'educationId', '-educationId', 'workId', '-workId'])) || (isset($params['gender']) && $params['gender'] != '') || (isset($params['educationId']) && $params['educationId'] != '') || (isset($params['workId']) && $params['workId'] != ''))
			$query = $query->joinWith(['respondent respondent', 'respondent.education education', 'respondent.work work']);
		if((isset($params['sort']) && in_array($params['sort'], ['service_id', '-service_id'])) || (isset($params['serviceName']) && $params['serviceName'] != ''))
			$query = $query->joinWith(['service service']);
		if((isset($params['sort']) && in_array($params['sort'], ['creationDisplayname', '-creationDisplayname'])) || (isset($params['creationDisplayname']) && $params['creationDisplayname'] != ''))
			$query = $query->joinWith(['creation creation']);
		if((isset($params['sort']) && in_array($params['sort'], ['modifiedDisplayname', '-modifiedDisplayname'])) || (isset($params['modifiedDisplayname']) && $params['modifiedDisplayname'] != ''))
			$query = $query->joinWith(['modified modified']);

		$query = $query->groupBy(['id']);

		// add conditions that should always apply here
		$dataParams = [
			'query' => $query,
		];
		// disable pagination agar data pada api tampil semua
		if(isset($params['pagination']) && $params['pagination'] == 0)
			$dataParams['pagination'] = false;
		$dataProvider = new ActiveDataProvider($dataParams);

		$attributes = array_keys($this->getTableSchema()->columns);
		$attributes['gender'] = [
			'asc' => ['respondent.gender' => SORT_ASC],
			'desc' => ['respondent.gender' => SORT_DESC],
		];
		$attributes['educationId'] = [
			'asc' => ['education.education_level' => SORT_ASC],
			'desc' => ['education.education_level' => SORT_DESC],
		];
		$attributes['workId'] = [
			'asc' => ['work.work_name' => SORT_ASC],
			'desc' => ['work.work_name' => SORT_DESC],
		];
		$attributes['service_id'] = [
			'asc' => ['service.service_name' => SORT_ASC],
			'desc' => ['service.service_name' => SORT_DESC],
		];
		$attributes['creationDisplayname'] = [
			'asc' => ['creation.displayname' => SORT_ASC],
			'desc' => ['creation.displayname' => SORT_DESC],
		];
		$attributes['modifiedDisplayname'] = [
			'asc' => ['modified.displayname' => SORT_ASC],
			'desc' => ['modified.displayname' => SORT_DESC],
		];
		$dataProvider->setSort([
			'attributes' => $attributes,
			'defaultOrder' => ['id' => SORT_DESC],
		]);

		if(Yii::$app->request->get('id'))
			unset($params['id']);
		$this->load($params);

		if(!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			't.id' => $this->id,
			't.respondent_id' => isset($params['respondent']) ? $params['respondent'] : $this->respondent_id,
			't.service_id' => isset($params['service']) ? $params['service'] : $this->service_id,
			'cast(t.creation_date as date)' => $this->creation_date,
			't.creation_id' => isset($params['creation']) ? $params['creation'] : $this->creation_id,
			'cast(t.modified_date as date)' => $this->modified_date,
			't.modified_id' => isset($params['modified']) ? $params['modified'] : $this->modified_id,
			'cast(t.updated_date as date)' => $this->updated_date,
			'respondent.gender' => $this->gender,
			'respondent.education_id' => $this->educationId,
			'respondent.work_id' => $this->workId,
		]);

		if(isset($params['trash']))
			$query->andFilterWhere(['NOT IN', 't.publish', [0,1]]);
		else {
			if(!isset($params['publish']) || (isset($params['publish']) && $params['publish'] == ''))
				$query->andFilterWhere(['IN', 't.publish', [0,1]]);
			else
				$query->andFilterWhere(['t.publish' => $this->publish]);
		}

		$query->andFilterWhere(['like', 'service.service_name', $this->serviceName])
			->andFilterWhere(['like', 'creation.displayname', $this->creationDisplayname])
			->andFilterWhere(['like', 'modified.displayname', $this->modifiedDisplayname]);

		return $dataProvider;
	}
}
