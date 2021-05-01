<?php

namespace mortscode\feedback\elements\db;

use craft\elements\db\ElementQuery;
use mortscode\feedback\enums\FeedbackStatus;

class FeedbackElementQuery extends ElementQuery
{
    public $entryId;
    public $name;
    public $email;
    public $rating;
    public $comment;
    public $status;
    public $response;
    public $ipAddress;
    public $userAgent;
    public $feedbackType;

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

    public function email($value): FeedbackElementQuery
    {
        $this->email = $value;
        return $this;
    }

    public function rating($value): FeedbackElementQuery
    {
        $this->rating = $value;
        return $this;
    }

    public function comment($value): FeedbackElementQuery
    {
        $this->comment = $value;
        return $this;
    }

    public function response($value): FeedbackElementQuery
    {
        $this->response = $value;
        return $this;
    }

    public function ipAddress($value): FeedbackElementQuery
    {
        $this->ipAddress = $value;
        return $this;
    }

    public function userAgent($value): FeedbackElementQuery
    {
        $this->userAgent = $value;
        return $this;
    }

    public function feedbackType($value): FeedbackElementQuery
    {
        $this->feedbackType = $value;
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
            'feedback_record.email',
            'feedback_record.rating',
            'feedback_record.comment',
            'feedback_record.response',
            'feedback_record.ipAddress',
            'feedback_record.userAgent',
            'feedback_record.feedbackType',
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

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.email',
                $this->email)
            );
        }

        if ($this->rating) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.rating',
                $this->rating)
            );
        }

        if ($this->comment) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.comment',
                $this->comment)
            );
        }

        if ($this->status) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.status',
                $this->status)
            );
        }

        if ($this->response) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.response',
                $this->response)
            );
        }

        if ($this->ipAddress) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.ipAddress',
                $this->ipAddress)
            );
        }

        if ($this->userAgent) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.userAgent',
                $this->userAgent)
            );
        }

        if ($this->feedbackType) {
            $this->subQuery->andWhere(Db::parseParam(
                'feedback_record.$this->feedbackType',
                $this->feedbackType)
            );
        }

        return parent::beforePrepare();
    }

    // Update Status options
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case FeedbackStatus::Approved:
                return ['isApproved' => true];
            case FeedbackStatus::Pending:
                return ['isPending' => true];
            case FeedbackStatus::Spam:
                return ['isSpam' => true];
            default:
                // call the base method for `enabled` or `disabled`
                return parent::statusCondition($status);
        }
    }
}