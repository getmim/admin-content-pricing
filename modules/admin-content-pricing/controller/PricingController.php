<?php
/**
 * PricingController
 * @package admin-content-pricing
 * @version 0.1.1
 */

namespace AdminContentPricing\Controller;

use LibFormatter\Library\Formatter;
use LibForm\Library\Form;
use LibForm\Library\Combiner;
use LibPagination\Library\Paginator;
use LibUser\Library\Fetcher;
use ContentPricing\Model\Pricing;

class PricingController extends \Admin\Controller
{
    private function getParams(string $title): array{
        return [
            '_meta' => [
                'title' => $title,
                'menus' => ['pricing']
            ],
            'subtitle' => $title,
            'pages' => null
        ];
    }

    public function editAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->set_content_pricing)
            return $this->show404();

        $pricing = (object)[];

        $id = $this->req->param->id;
        if($id){
            $cond = ['id'=>$id];
            if(!$this->can_i->read_content_pricing_all)
                $cond['user'] = $this->user->id;
            $pricing = Pricing::getOne($cond);
            if(!$pricing)
                return $this->show404();
        }

        $form = new Form('admin.content-pricing.edit');
        $next = $this->req->get('reff');
        if(!$next)
            $next = $this->router->to('adminContentPricing');

        if(!($valid = $form->validate($pricing)) || !$form->csrfTest('noob'))
            return $this->res->redirect($next);

        $valid->month.= '-01';

        $conf = $this->config->contentPricing->objects->{$valid->type};

        $o_model = $conf->model;
        $o_f_id  = $conf->fields->id;
        $o_f_user= $conf->fields->user;

        $object = $o_model::getOne([$o_f_id=>$valid->object]);
        if(!$object)
            return $this->res->redirect($next);

        $valid->creator = $object->{$o_f_user};

        if($id){
            if(!Pricing::set((array)$valid, ['id'=>$id]))
                deb(Pricing::lastError());
        }else{
            $valid->user = $this->user->id;
            if(!($id = Pricing::create((array)$valid)))
                deb(Pricing::lastError());
        }

        // add the log
        $this->addLog([
            'user'   => $this->user->id,
            'object' => $id,
            'parent' => 0,
            'method' => $id ? 2 : 1,
            'type'   => 'pricing',
            'original' => $pricing,
            'changes'  => $valid
        ]);

        $this->res->redirect($next);
    }

    public function indexAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->read_content_pricing)
            return $this->show404();

        $cond = $pcond = $this->req->getCond(['user','month','status','type']);

        if(!isset($pcond['type']))
            $cond['type'] = $pcond['type'] = 'all';
        if(!isset($pcond['status']))
            $cond['status'] = $pcond['status'] = 1;
        if(!isset($pcond['month']))
            $cond['month'] = $pcond['month'] = date('Y-m');

        list($page, $rpp) = $this->req->getPager(25, 50);

        $params          = $this->getParams('Pricing');
        $params['total'] = 0;
        $params['filt_users'] = [];

        if(isset($cond['user'])){
            $user = Fetcher::getOne(['id'=>$cond['user']]);
            if($user)
                $params['filt_users'][$user->id] = $user->fullname;
        }

        $accepted_types = ['all' => 'All'];
        $types          = $this->config->libEnum->enums->{'content-pricing.type'};
        $type_active    = $this->config->contentPricing->active;
        if($type_active){
            foreach($type_active as $type => $act){
                if(!$act)
                    continue;
                $accepted_types[$type] = $types[ $type ];
            }
        }
        asort($types);

        if(isset($cond['status']) && $cond['status'] == 2){
            $params['type'] = 'unpriced';


            $filt_user = $cond['user'] ?? null;
            if(!$this->can_i->read_content_pricing_all)
                $filt_user = $this->user->id;

            if($cond['type'] === 'all'){
                foreach($accepted_types as $key => $val){
                    if($key === 'all')
                        continue;
                    $cond['type'] = $pcond['type'] = $key;
                    break;
                }
            }

            $conf = $this->config->contentPricing->objects->{$cond['type']};

            $pricings = [];

            $objects = Pricing::getUnpriced($cond['type'], $conf, $filt_user, $rpp, $page);
            $o_format = $conf->format;
            $o_f_user = $conf->fields->user;
            if($objects){
                $objects = Formatter::formatMany($o_format, $objects, [$o_f_user]);
                foreach($objects as $obj){
                    $pricings[] = (object)[
                        'id'      => 0,
                        'user'    => null,
                        'creator' => $obj->$o_f_user,
                        'object'  => $obj,
                        'type'    => $cond['type'],
                        'price'   => new \LibFormatter\Object\Number(0),
                        'month'   => null
                    ];
                }
            }

            $params['total'] = Pricing::countUnpriced($cond['type'], $conf, $filt_user);

        }else{
            $params['type'] = 'priced';

            if(isset($cond['status']))
                unset($cond['status']);

            $cond['month'] = $cond['month'] . '-01';

            if(isset($cond['type']) && $cond['type'] === 'all')
                unset($cond['type']);

            $pricings = Pricing::get($cond, 0, 1, ['created'=>false]) ?? [];
            if($pricings)
                $pricings = Formatter::formatMany('content-pricing', $pricings, ['user','creator','object']);

            // pagination
            $params['total'] = Pricing::count($cond);
        }

        $total = $params['total'];
        if($total > $rpp){
            $params['pages'] = new Paginator(
                $this->router->to('adminContentPricing'),
                $total,
                $page,
                $rpp,
                10,
                $pcond
            );
        }

        $params['types'] = $accepted_types;
        $params['form']  = new Form('admin.content-pricing.index');
        $params['form']->validate( (object)$pcond );

        $params['f_pricing'] = new Form('admin.content-pricing.edit');

        $result = [];
        foreach($pricings as $prc){
            $obj       = $prc->object;
            $obj_final = (object)[];
            $obj_type  = $prc->type;

            $conf = $this->config->contentPricing->objects->{$obj_type};
            $o_fields = $conf->fields;

            foreach($o_fields as $key => $fld){
                $value = $obj->$fld ?? null;
                if(substr($fld,0,1) === '$')
                    $value = substr($fld,1);
                $obj_final->$key = $value;
            }

            $prc->object = $obj_final;

            $user_id = $prc->object->user->id;
            if(is_object($user_id))
                $user_id = $user_id->value;

            if(!isset($result[$user_id]))
                $result[$user_id] = [];
            $result[$user_id][] = $prc;
        }

        $params['pricings'] = $result;

        $this->resp('content-pricing/index', $params);
    }

    public function removeAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->remove_content_pricing)
            return $this->show404();

        $id      = $this->req->param->id;
        $pricing = Pricing::getOne(['id'=>$id]);
        $next    = $this->req->get('reff') ?? $this->router->to('adminContentPricing');
        $form    = new Form('admin.content-pricing.index');

        if(!$form->csrfTest('noob'))
            return $this->res->redirect($next);

        // add the log
        $this->addLog([
            'user'   => $this->user->id,
            'object' => $id,
            'parent' => 0,
            'method' => 3,
            'type'   => 'pricing',
            'original' => $pricing,
            'changes'  => null
        ]);

        Pricing::remove(['id'=>$id]);

        $this->res->redirect($next);
    }
}
