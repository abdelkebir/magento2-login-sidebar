<?php
namespace Godogi\LoginSidebar\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\SecurityViolationException;

class Forgot extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $customerAccountManagement;
    public function __construct(
            Context $context,
            JsonFactory $resultJsonFactory,
            AccountManagementInterface $customerAccountManagement)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        parent::__construct($context);
    }
    public function execute()
    {
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                $result = $this->resultJsonFactory->create();
                $result->setData(['success' => false, 'message' => 'Please correct the email address.']);
                return $result;
            }
            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
            } catch (NoSuchEntityException $exception) {
            } catch (SecurityViolationException $exception) {
                $result = $this->resultJsonFactory->create();
                $result->setData(['success' => false, 'message' => $exception->getMessage()]);
                return $result;
            } catch (\Exception $exception) {
                $result = $this->resultJsonFactory->create();
                $result->setData(['success' => false, 'message' => __('We\'re unable to send the password reset email.')]);
                return $result;
            }
            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'message' => __('We are processing your request.')]);
            return $result;
        } else {
            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'message' => 'Please enter your email.']);
            return $result;
        }
    }
}