<?php
namespace Godogi\LoginSidebar\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Exception\LocalizedException;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $session;
    protected $formKeyValidator;
    protected $resultJsonFactory;
    protected $customerAccountManagement;

    public function __construct(
            Context $context, 
            Session $customerSession,
            Validator $formKeyValidator,
            JsonFactory $resultJsonFactory,
            AccountManagementInterface $customerAccountManagement,
            CustomerUrl $customerHelperData)
    {
        $this->session = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        parent::__construct($context);
    }
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'message' => 'Please refresh the page!']);
            return $result;
        }
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => true, 'message' => 'Customer exist.']);
                    return $result;
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    return $result;
                } catch (UserLockedException $e) {
                    $message = __(
                        'You did not sign in correctly or your account is temporarily disabled.'
                    );
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    return $result;
                } catch (AuthenticationException $e) {
                    $message = __('You did not sign in correctly or your account is temporarily disabled.');
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    return $result;
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    return $result;
                } catch (\Exception $e) {
                    $message = __('An unspecified error occurred. Please contact us for assistance.');
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    return $result;
                }
            }else{
                $result = $this->resultJsonFactory->create();
                $result->setData(['success' => false, 'message' => 'A login and a password are required.']);
                return $result;
            }
        }else{
            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'message' => 'You don\'t have permission to access this page!']);
            return $result;
        }
    }
}