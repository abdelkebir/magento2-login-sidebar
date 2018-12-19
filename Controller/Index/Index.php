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
use Godogi\LoginSidebar\Helper\Data as CustomCooike;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $session;
    protected $formKeyValidator;
    protected $resultJsonFactory;
    protected $customerAccountManagement;

    private $getCookiedata;
    private $cookieMetadataManager;
    private $cookieMetadataFactory;

    public function __construct(
            Context $context, 
            Session $customerSession,
            Validator $formKeyValidator,
            JsonFactory $resultJsonFactory,
            AccountManagementInterface $customerAccountManagement,
            CustomerUrl $customerHelperData,
            CustomCooike $getCookiedata)
    {
        $this->session = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        $this->getCookiedata = $getCookiedata;
        parent::__construct($context);
    }
    /**
    * Get scope config
    *
    * @return ScopeConfigInterface
    * @deprecated
    */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
               \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }
    /**
    * Retrieve cookie manager
    *
    * @deprecated
    * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
    */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
               \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }
    /**
    * Retrieve cookie metadata factory
    *
    * @deprecated
    * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
    */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
               \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
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
                    if (array_key_exists('rememberme',$login)) {
                        $logindetails = array('username' => $login['username'], 'password' => $login['password'], 'remchkbox' => 1);
                        $logindetails = json_encode($logindetails);
                        $this->getCookiedata->set($logindetails, $this->getCookiedata->getCookielifetime());
                    } else {
                        $this->getCookiedata->delete('remember');
                    }





                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }

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
                    $this->session->setUsername($login['username']);
                    return $result;
                } catch (UserLockedException $e) {
                    $message = __(
                        'You did not sign in correctly or your account is temporarily disabled.'
                    );
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    $this->session->setUsername($login['username']);
                    return $result;
                } catch (AuthenticationException $e) {
                    $message = __('You did not sign in correctly or your account is temporarily disabled.');
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    $this->session->setUsername($login['username']);
                    return $result;
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                    $result = $this->resultJsonFactory->create();
                    $result->setData(['success' => false, 'message' => $message]);
                    $this->session->setUsername($login['username']);
                    return $result;
                } catch (\Exception $e) {
                    $message = __('An unspecified error occurred. Please contact us for assistance.');
                    $message = $e->getMessage();
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