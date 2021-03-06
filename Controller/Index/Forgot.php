<?php
namespace Godogi\LoginSidebar\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Escaper;

class Forgot extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $customerAccountManagement;
    protected $escaper;
    public function __construct(
            Context $context,
            JsonFactory $resultJsonFactory,
            AccountManagementInterface $customerAccountManagement,
            Escaper $escaper)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        parent::__construct($context);
    }
    public function execute()
    {
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            $result->setData(['success' => true, 'message' => $this->getSuccessMessage($email)]);
            return $result;
        } else {
            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'message' => 'Please enter your email.']);
            return $result;
        }
    }

    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to reset your password.',
            $this->escaper->escapeHtml($email)
        );
    }
}