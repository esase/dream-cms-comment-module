<?php
namespace Comment\Model;

use Comment\Event\CommentEvent;
use Application\Model\ApplicationAbstractBase;
use Application\Utility\ApplicationErrorLogger;
use Zend\Db\ResultSet\ResultSet;
use Exception;

class CommentBase extends ApplicationAbstractBase
{
    /**
     * Comment model instance
     * @var object  
     */
    protected $commentModel;

    /**
     * Get comment model
     */
    public function getCommentModel()
    {
        if (!$this->commentModel) {
            $this->commentModel = $this->serviceLocator->get('Comment\Model\CommentNestedSet');
        }

        return $this->commentModel;
    }

    /**
     * Is spam IP
     *
     * @param string $ip
     * @return boolean
     */
    public function isSpamIp($ip)
    {
        $select = $this->select();
        $select->from('comment_spam_ip')
            ->columns([
                'id'
            ])
            ->where([
                'ip' => inet_pton($ip)
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }

    /**
     * Spam comment
     *
     * @param string $ip
     * @return boolean|string
     */
    public function spamComment($ip)
    {
        if (!$this->isSpamIp($ip)) {
            try {
                $this->adapter->getDriver()->getConnection()->beginTransaction();

                $insert = $this->insert()
                    ->into('comment_spam_ip')
                    ->values([
                        'ip' => inet_pton($ip)
                    ]);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
                $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

                $this->adapter->getDriver()->getConnection()->commit();
            }
            catch (Exception $e) {
                $this->adapter->getDriver()->getConnection()->rollback();
                ApplicationErrorLogger::log($e);

                return $e->getMessage();
            }

            // fire the add comment spam IP event
            CommentEvent::fireAddCommentSpamIpEvent($insertId);
        }

        return true;
    }
}