<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\ThirdPartyFilter;
use Biz\Constants;
use Biz\ThirdParty\Service\ThirdPartyService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use support\Request;
use support\utils\Paginator;
use support\utils\ArrayToolkit;
use support\view\Raw;

class ThirdParty extends BaseController
{
    public function itemNvrTree(Request $request)
    {
        $params = $request->get();
        $conditions['locked'] = 0;
        if (!empty($params['keywords'])) {
            $conditions['partnerNameLike'] = $params['keywords'];
        }

        $thirdParties = $this->getThirdPartyService()->searchThirdParties($conditions, ['createdTime' => 'DESC'], 0, PHP_INT_MAX, ['id', 'partner_name']);
    }

    public function index(Request $request)
    {
        $conditions = [];
        $params = $request->get();
        if (!empty($params['keywords'])) {
            $conditions['partnerNameLike'] = $params['keywords'];
        }

        if (isset($params['locked']) && '' !== $params['locked']) {
            $conditions['locked'] = $params['locked'];
        }

        $total = $this->getThirdPartyService()->countThirdParties($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['locked'] = 'ASC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $thirdParties = $this->getThirdPartyService()->searchThirdParties($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new ThirdPartyFilter();
        $filter->filters($thirdParties);

        return $this->createSuccessJsonResponse([
            'thirdParties' => $thirdParties,
            'paginator' => Paginator::toArray($paginator)
        ]);

    }

    public function items(Request $request)
    {
        $params = $request->get();
        $conditions['locked'] = 0;
        if (!empty($params['keywords'])) {
            $conditions['partnerNameLike'] = $params['keywords'];
        }

        $thirdParties = $this->getThirdPartyService()->searchThirdParties($conditions, ['createdTime' => 'DESC'], 0, PHP_INT_MAX, ['id', 'partner_name']);

        return $this->createSuccessJsonResponse($thirdParties);
    }

    public function create(Request $request)
    {
        $formData = $request->post();
        if (!empty($formData['params']) && is_string($formData['params'])) {
            $params = json_decode($formData['params'], true);
            if (!$params) {
                $formData['params'] = '';
            } else {
                $formData['params'] = $params;
            }
        }

        $thirdParty = $this->getThirdPartyService()->createThirdParty($formData);

        return $this->createSuccessJsonResponse();
    }

    public function edit(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        $thirdParty = $this->getThirdPartyService()->getThirdParty($id);
        if (empty($thirdParty)) {
            throw  new NotFoundException("合作方不存在");
        }

        $formData = $request->post();
        if (!empty($formData['params']) && is_string($formData['params'])) {
            $params = json_decode($formData['params'], true);
            if (!$params) {
                $formData['params'] = $thirdParty['params'];
            } else {
                $formData['params'] = $params;
            }
        }

        $thirdParty = $this->getThirdPartyService()->updateThirdParty($id, $formData);

        return $this->createSuccessJsonResponse();
    }

    public function lock(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        $thirdParty = $this->getThirdPartyService()->getThirdParty($id);
        if (empty($thirdParty)) {
            throw  new NotFoundException("合作方不存在");
        }

        $this->getThirdPartyService()->lockThirdParty($id);

        return $this->createSuccessJsonResponse();

    }

    public function unlock(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        $thirdParty = $this->getThirdPartyService()->getThirdParty($id);
        if (empty($thirdParty)) {
            throw  new NotFoundException("合作方不存在");
        }

        $this->getThirdPartyService()->unlockThirdParty($id);

        return $this->createSuccessJsonResponse();
    }

    public function locks(Request $request)
    {
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        is_string($ids) && $ids = explode(',', $ids);
        $thirdParties = $this->getThirdPartyService()->searchThirdParties(['ids' => $ids], [], 0, PHP_INT_MAX, ['id']);
        $lockIds = ArrayToolkit::column($thirdParties, 'id');
        $count = $this->getThirdPartyService()->lockThirdParties($lockIds);

        if (!$count) {
            return $this->createFailJsonResponse('批量封禁失败了');
        }

        return $this->createSuccessJsonResponse([
            'finishCount' => $count
        ]);
    }

    public function unlocks(Request $request)
    {
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        is_string($ids) && $ids = explode(',', $ids);
        $thirdParties = $this->getThirdPartyService()->searchThirdParties(['ids' => $ids], [], 0, PHP_INT_MAX, ['id']);
        $lockIds = ArrayToolkit::column($thirdParties, 'id');
        $count = $this->getThirdPartyService()->unlockThirdParties($lockIds);

        if (!$count) {
            return $this->createFailJsonResponse('批量解禁失败了');
        }

        return $this->createSuccessJsonResponse([
            'finishCount' => $count
        ]);
    }

    public function liveProviderItems(Request $request)
    {
        return $this->createSuccessJsonResponse(Constants::getLiveProviderItems());
    }

    /**
     *
     * @return ThirdPartyService
     */
    protected function getThirdPartyService()
    {
        return $this->createService('ThirdParty:ThirdPartyService');
    }
}