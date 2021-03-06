<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\Plugin\RoomTagPull\Action\Dashboard;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use MoChat\App\Common\Middleware\DashboardAuthMiddleware;
use MoChat\App\Rbac\Middleware\PermissionMiddleware;
use MoChat\App\WorkContact\Contract\WorkContactContract;
use MoChat\App\WorkContact\Contract\WorkContactRoomContract;
use MoChat\Framework\Action\AbstractAction;
use MoChat\Framework\Constants\ErrorCode;
use MoChat\Framework\Exception\CommonException;
use MoChat\Framework\Request\ValidateSceneTrait;
use MoChat\Plugin\RoomTagPull\Action\Dashboard\Traits\UpdateTrait;
use Psr\Container\ContainerInterface;

/**
 * Class FilterContact.
 * @Controller
 */
class FilterContact extends AbstractAction
{
    use ValidateSceneTrait;
    use UpdateTrait;

    /**
     * @Inject
     * @var WorkContactContract
     */
    protected $workContactService;

    /**
     * @Inject
     * @var WorkContactRoomContract
     */
    protected $workContactRoomService;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(RequestInterface $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

    /**
     * @Middlewares({
     *     @Middleware(DashboardAuthMiddleware::class),
     *     @Middleware(PermissionMiddleware::class)
     * })
     * @RequestMapping(path="/dashboard/roomTagPull/filterContact", methods="post")
     * @throws \JsonException
     */
    public function handle(): array
    {
        $user = user();
        ## ??????????????????????????????
        if (! isset($user['corpIds']) || count($user['corpIds']) != 1) {
            throw new CommonException(ErrorCode::INVALID_PARAMS, '????????????????????????????????????');
        }

        ## ????????????
        $params = $this->request->all();
        $this->validated($params);
        ## ????????????
        $count = $this->filterContact(1, $user, $params['rooms'], $params['employees'], $params['choose_contact']);
        return [$count];
//        return $this->handleData($user, $params);
    }

    /**
     * ????????????.
     *
     * @return array ????????????
     */
    protected function rules(): array
    {
        return [
        ];
    }

    /**
     * ??????????????????.
     * @return array ????????????
     */
    protected function messages(): array
    {
        return [
        ];
    }

    private function handleData(array $user, array $params): array
    {
        $count = 0;
        foreach ($params['rooms'] as $room) {
            $roomContact = $this->workContactRoomService->getWorkContactRoomsByRoomIdContact((int) $room['id'], ['contact_id']);
            $contact = $this->workContactService->getWorkContactsBySearch($user['corpIds'][0], $params['employees'], $params['choose_contact']);
            foreach ($contact as $k => $v) {
                if (in_array($v['contactId'], array_column($roomContact, 'contactId'), true)) {
                    unset($contact[$k]);
                }
            }
            $count += count($contact);
        }

        return [$count];
    }
}
