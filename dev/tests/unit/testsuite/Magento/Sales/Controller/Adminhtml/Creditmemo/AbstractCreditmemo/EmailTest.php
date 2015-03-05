<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Controller\Adminhtml\Creditmemo\AbstractCreditmemo;

use Magento\Framework\App\Action\Context;
use Magento\TestFramework\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class EmailTest
 *
 * @package Magento\Sales\Controller\Adminhtml\Creditmemo\AbstractCreditmemo
 */
class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Email
     */
    protected $creditmemoEmail;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectFactoryMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    public function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->getMock(
            'Magento\Backend\App\Action\Context',
            [
                'getRequest',
                'getResponse',
                'getMessageManager',
                'getRedirect',
                'getObjectManager',
                'getSession',
                'getActionFlag',
                'getHelper'
            ],
            [],
            '',
            false
        );
        $this->response = $this->getMock(
            'Magento\Framework\App\ResponseInterface',
            ['setRedirect', 'sendResponse'],
            [],
            '',
            false
        );

        $this->request = $this->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()->getMock();
        $this->objectManager = $this->getMock(
            'Magento\Framework\ObjectManager\ObjectManager',
            ['create'],
            [],
            '',
            false
        );
        $this->messageManager = $this->getMock('Magento\Framework\Message\Manager', ['addSuccess'], [], '', false);
        $this->session = $this->getMock('Magento\Backend\Model\Session', ['setIsUrlNotice'], [], '', false);
        $this->actionFlag = $this->getMock('Magento\Framework\App\ActionFlag', ['get'], [], '', false);
        $this->helper = $this->getMock('\Magento\Backend\Helper\Data', ['getUrl'], [], '', false);
        $this->resultRedirectFactoryMock = $this->getMockBuilder('Magento\Backend\Model\View\Result\RedirectFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirectMock = $this->getMockBuilder('Magento\Backend\Model\View\Result\Redirect')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->will($this->returnValue($this->messageManager));
        $this->context->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($this->request));
        $this->context->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->response));
        $this->context->expects($this->once())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManager));
        $this->context->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->session));
        $this->context->expects($this->once())
            ->method('getActionFlag')
            ->will($this->returnValue($this->actionFlag));
        $this->context->expects($this->once())
            ->method('getHelper')
            ->will($this->returnValue($this->helper));
        $this->creditmemoEmail = $objectManagerHelper->getObject(
            'Magento\Sales\Controller\Adminhtml\Creditmemo\AbstractCreditmemo\Email',
            [
                'context' => $this->context,
                'request' => $this->request,
                'response' => $this->response,
                'resultRedirectFactory' => $this->resultRedirectFactoryMock
            ]
        );
    }

    public function testEmail()
    {
        $cmId = 10000031;
        $creditmemoClassName = 'Magento\Sales\Model\Order\Creditmemo';
        $cmNotifierClassName = 'Magento\Sales\Model\Order\CreditmemoNotifier';
        $creditmemo = $this->getMock($creditmemoClassName, ['load', '__wakeup'], [], '', false);
        $cmNotifier = $this->getMock($cmNotifierClassName, ['notify', '__wakeup'], [], '', false);
        $this->prepareRedirect($cmId);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('creditmemo_id')
            ->will($this->returnValue($cmId));
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($creditmemoClassName)
            ->will($this->returnValue($creditmemo));
        $creditmemo->expects($this->once())
            ->method('load')
            ->with($cmId)
            ->will($this->returnSelf());
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with($cmNotifierClassName)
            ->will($this->returnValue($cmNotifier));
        $cmNotifier->expects($this->once())
            ->method('notify')
            ->will($this->returnValue(true));
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with('We sent the message.');

        $this->assertInstanceOf(
            'Magento\Backend\Model\View\Result\Redirect',
            $this->creditmemoEmail->execute()
        );
        $this->assertEquals($this->response, $this->creditmemoEmail->getResponse());
    }

    public function testEmailNoCreditmemoId()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('creditmemo_id')
            ->will($this->returnValue(null));

        $this->assertNull($this->creditmemoEmail->execute());
    }

    public function testEmailNoCreditmemo()
    {
        $cmId = 10000031;
        $creditmemoClassName = 'Magento\Sales\Model\Order\Creditmemo';
        $creditmemo = $this->getMock($creditmemoClassName, ['load', '__wakeup'], [], '', false);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('creditmemo_id')
            ->will($this->returnValue($cmId));
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with($creditmemoClassName)
            ->will($this->returnValue($creditmemo));
        $creditmemo->expects($this->once())
            ->method('load')
            ->with($cmId)
            ->will($this->returnValue(null));

        $this->assertNull($this->creditmemoEmail->execute());
    }

    /**
     * @param int $cmId
     */
    protected function prepareRedirect($cmId)
    {
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('sales/order_creditmemo/view', ['creditmemo_id' => $cmId])
            ->willReturnSelf();
    }
}
