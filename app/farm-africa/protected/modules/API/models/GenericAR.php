<?php

/**
 * Description of GenericAR
 * this class extends CActiveRecord, all models will extend this one
 * it performs additional validation/actions that is required for all models, 
 * specific to the FarmAfrica project
 * @author muya
 */
class GenericAR extends CActiveRecord {

    const CREATE = 1;
    const UPDATE = 2;
    const VIEW = 3;
    const DELETE = 4;

    /**
     * @var int $status 
     */
    public $status;

    /**
     * @var string $dateCreated the date on which the record was created
     */
    public $dateCreated;

    /**
     * @var int the userID of the user who created the record
     */
    public $createdBy;

    /**
     * @var string the date on which the record was last modified 
     */
    public $dateModified;

    /**
     * @var int the userID of the user who last modified the record 
     */
    public $modifiedBy;

    /**
     * @var string reasons for performing certain actions on the system 
     */
    public $narration;
    
    /**
     * @var array array having any extra info to be sent back to caller application
     */
    public $extraData;

    /**
     * this function determines what action to perform on the given model
     * @param int $action
     * @param boolean $override
     */
    public function modelAction($action, $override = false) {
        Utils::log('INFO', 'ABOUT TO PERFORM MODEL ACTION: ' . $action, __CLASS__, __FUNCTION__, __LINE__);
        $modelActionResponse = array();
        switch ($action) {
            case self::CREATE:
                $actionResponse = $this->createModel($override);
                break;
            case self::UPDATE:
                $actionResponse = $this->updateModel($override);
                break;
            case self::VIEW:
                break;
            case self::DELETE:
                break;
            default:
                break;
        }
        $modelActionResponse['STATUS'] = $actionResponse['STATUS'];
        $modelActionResponse['DATA'] = $actionResponse['DATA'];
        $modelActionResponse['DATA']['EXTRA'] = CJSON::encode($this->extraData);
        $modelActionResponse['DESCRIPTION'] = $actionResponse['REASON'];
        return $modelActionResponse;
    }
    
    /**
     * function to update a model
     * @param type $override
     * @return \GenericAR
     */
    public function updateModel($override = false){
        Utils::log('INFO', 'ABOUT TO PERFORM ACTION UPDATE', __CLASS__, __FUNCTION__, __LINE__);
        $actionResponse = array();
        
        $this->dateModified = Utils::now();
        
        try {
            $actionResponse = array();
            $actionResponse['STATUS'] = $this->save();
            
            if (!$actionResponse['STATUS']) {
                //save failed
                $actionResponse['DATA']['ERROR'] = $this->getErrors();
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'failedToUpdateThe{model}Record', array('{model}' => ucfirst($this->tableName())));
                Utils::log('ERROR', 'AN ERROR OCCURRED WHILE TRYING TO SAVE THE MODEL'.CJSON::encode($actionResponse), __CLASS__, __FUNCTION__, __LINE__);
                
            } else {
                //save was ok
                Utils::log('INFO', 'MODEL WAS UPDATED SUCCESSFULLY', __CLASS__, __FUNCTION__, __LINE__);
                $actionResponse['DATA']['PK'] = $this->primaryKey;
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'successfullyUpdated{model}Record', array('{model}' => ucfirst($this->tableName())));
            }
        } catch (CDbException $dbExc) {
            Utils::log('EXCEPTION', 'A CDbException OCCURRED WHILE TRYING TO UPDATE THE MODEL | '.CJSON::encode($dbExc) , __CLASS__, __FUNCTION__, __LINE__);
            $actionResponse['STATUS'] = false;
            $actionResponse['DATA']['ERROR'] = $dbExc;

            if ($dbExc->getCode() == 23000) {
                //duplicate record/field error
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'sorryTheOperationCannotBePerformedSinceASimilarEntryAlreadyExistsInTheSystem');
            } else {
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'anErrorOccurredWhileUpdatingThe{model}Record', array('{model}' => ucfirst($this->tableName())));
            }
        } catch (Exception $exc) {
            Utils::log('EXCEPTION', 'A Exception OCCURRED WHILE TRYING TO UPDATE THE MODEL | '.CJSON::encode($exc) , __CLASS__, __FUNCTION__, __LINE__);
            $actionResponse['STATUS'] = false;
            $actionResponse['DATA']['ERROR'] = $dbExc;
            $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'anErrorOccurredWhileUpdatingThe{model}Record', array('{model}' => ucfirst($this->tableName())));
        }
        $actionResponse['DATA']['model'] = $this;
        return $actionResponse;
    }

    /**
     * this function creates and saves a model
     * it will save default values for the model
     * @param GenericAR $model
     * @param boolean $override
     */
    private function createModel($override = false) {
        Utils::log('INFO', 'ABOUT TO PERFORM ACTION CREATE', __CLASS__, __FUNCTION__, __LINE__);
        $actionResponse = array();
        
        $this->dateCreated = Utils::now();
        $this->dateModified = Utils::now();
        $this->status = (!isset($this->status) || ($this->status == '')) ? StatCodes::ES_ACTIVE : $this->status;
        $this->modifiedBy = (!isset($this->modifiedBy) || ($this->modifiedBy == '')) ? PermissionUtils::SUPER_USER_ID : $this->modifiedBy;
        $this->createdBy = (!isset($this->createdBy) || ($this->createdBy == '')) ? PermissionUtils::SUPER_USER_ID : $this->createdBy;
        
        try {
            $actionResponse = array();
            $actionResponse['STATUS'] = $this->save();
            
            if (!$actionResponse['STATUS']) {
                //save failed
                $actionResponse['DATA']['ERROR'] = $this->getErrors();
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'failedToCreateThe{model}Record', array('{model}' => ucfirst($this->tableName())));
                Utils::log('ERROR', 'AN ERROR OCCURRED WHILE TRYING TO SAVE THE '. $this->tableName() .' MODEL'.CJSON::encode($actionResponse), __CLASS__, __FUNCTION__, __LINE__);
                
            } else {
                //save was ok
                Utils::log('INFO', 'MODEL WAS SAVED SUCCESSFULLY', __CLASS__, __FUNCTION__, __LINE__);
                $actionResponse['DATA']['PK'] = $this->primaryKey;
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'successfullyCreated{model}Record', array('{model}' => ucfirst($this->tableName())));
            }
        } catch (CDbException $dbExc) {
            Utils::log('EXCEPTION', 'A CDbException OCCURRED WHILE TRYING TO SAVE THE MODEL | '.CJSON::encode($dbExc) , __CLASS__, __FUNCTION__, __LINE__);
            $actionResponse['STATUS'] = false;
            $actionResponse['DATA']['ERROR'] = $dbExc;

            if ($dbExc->getCode() == 23000) {
                //duplicate record/field error
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'sorryTheOperationCannotBePerformedSinceASimilarEntryAlreadyExistsInTheSystem');
            } else {
                $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'anErrorOccurredWhileCreatingThe{model}Record', array('{model}' => ucfirst($this->tableName())));
            }
        } catch (Exception $exc) {
            Utils::log('EXCEPTION', 'A Exception OCCURRED WHILE TRYING TO SAVE THE MODEL | '.CJSON::encode($exc) , __CLASS__, __FUNCTION__, __LINE__);
            $actionResponse['STATUS'] = false;
            $actionResponse['DATA']['ERROR'] = $dbExc;
            $actionResponse['REASON'] = Yii::t(Yii::app()->language, 'anErrorOccurredWhileCreatingThe{model}Record', array('{model}' => ucfirst($this->tableName())));
        }
        $actionResponse['DATA']['model'] = $this;
        return $actionResponse;
    }
    
    public function returnableForeignKeyFields(){
        return array(
            'createdBy',
            'modifiedBy',
            'dateCreated',
            'dateModified'
        );
    }

}

?>
