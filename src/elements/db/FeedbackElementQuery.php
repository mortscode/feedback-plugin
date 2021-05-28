<?php

namespace mortscode\feedback\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use mortscode\feedback\enums\FeedbackStatus;

class FeedbackElementQuery extends ElementQuery
{
    public $entryId;
    public $name;
    public $rating;
    public $feedbackType;
    public $feedbackStatus;

    public function entryId($value): FeedbackElementQuery
    {
        $this->entryId = $value;
        return $this;
    }

    public function name($value): FeedbackElementQuery
    {
        $this->name = $value;
        return $this;
    }

    public function rating($value): FeedbackElementQuery
    {
        $this->rating = $value;
        return $this;
    }

    public function feedbackType($value): FeedbackElementQuery
    {
        $this->feedbackType = $value;
        return $this;
    }

    public function feedbackStatus($value): FeedbackElementQuery
    {
        $this->feedbackStatus = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the reviews table
        $this->joinElementTable('feedback_record');

        // select the column
        $this->query->select([
            'feedback_record.entryId',
            'feedback_record.name',
            'feedback_record.rating',
            'feedback_record.feedbackType',
            'feedback_record.feedbackStatus',
        ]);

        if ($this->entryId) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.entryId',
                $this->entryId)
            );
        }

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.name',
                $this->name)
            );
        }

        if ($this->rating) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.rating',
                $this->rating)
            );
        }

        if ($this->feedbackType) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.feedbackType',
                $this->feedbackType)
            );
        }

        if ($this->feedbackStatus) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.feedbackStatus',
                $this->feedbackStatus)
            );
        }

        return parent::beforePrepare();
    }

    // Update Status options
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case FeedbackStatus::Approved:
                return ['feedbackStatus' => FeedbackStatus::Approved];
            case FeedbackStatus::Pending:
                return ['feedbackStatus' => FeedbackStatus::Pending];
            case FeedbackStatus::Spam:
                return ['feedbackStatus' => FeedbackStatus::Spam];
            default:
                // call the base method for `enabled` or `disabled`
                return parent::statusCondition($status);
        }
    }
}