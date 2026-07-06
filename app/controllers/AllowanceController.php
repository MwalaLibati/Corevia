<?php
declare(strict_types=1);

class AllowanceController extends Controller
{
    public function index(): void
    {
        require_auth(); require_role(['Super Admin','Finance Officer']); $m=new Allowance();
        $this->render('allowances/index',['title'=>'Allowance Types','allowances'=>$m->findAll(),'csrf'=>Session::csrfToken(),'flashSuccess'=>Session::flash('success'),'flashError'=>Session::flash('error')]);
    }
    public function store(): void
    {
        require_auth(); require_role(['Super Admin','Finance Officer']); if($_SERVER['REQUEST_METHOD']!=='POST'||!Session::verifyCsrf((string)$this->input('_csrf',''))){redirect('allowance/index');}
        $m=new Allowance();$data=$this->data();if($data['name']===''||$data['code']===''){Session::flash('error','Name and code are required.');redirect('allowance/index');}if($m->codeExists($data['code'])){Session::flash('error','Allowance code already exists.');redirect('allowance/index');}
        $m->insert($data);AuditLog::record('allowance_create','Created allowance '.$data['name'],'AllowanceType');Session::flash('success','Allowance type created.');redirect('allowance/index');
    }
    public function update(string $id): void
    {
        require_auth(); require_role(['Super Admin','Finance Officer']);$aid=(int)$id;if($_SERVER['REQUEST_METHOD']!=='POST'||!Session::verifyCsrf((string)$this->input('_csrf',''))){redirect('allowance/index');}$m=new Allowance();$data=$this->data();if($m->codeExists($data['code'],$aid)){Session::flash('error','Allowance code already exists.');redirect('allowance/index');}$m->update($aid,$data);AuditLog::record('allowance_update','Updated allowance '.$data['name'],'AllowanceType',$aid);Session::flash('success','Allowance type updated.');redirect('allowance/index');
    }
    public function delete(string $id): void
    {
        require_auth();require_role(['Super Admin','Finance Officer']);$aid=(int)$id;if($_SERVER['REQUEST_METHOD']==='POST'&&Session::verifyCsrf((string)$this->input('_csrf',''))){(new Allowance())->update($aid,['is_active'=>0]);Session::flash('success','Allowance type deactivated.');}redirect('allowance/index');
    }
    private function data(): array
    {
        return ['name'=>trim((string)$this->input('name','')),'code'=>strtoupper(preg_replace('/[^A-Za-z0-9]/','',trim((string)$this->input('code','')))),'calculation_type'=>in_array($this->input('calculation_type','Fixed'),['Fixed','Percent'],true)?$this->input('calculation_type','Fixed'):'Fixed','default_value'=>max(0,(float)$this->input('default_value',0)),'is_taxable'=>(int)$this->input('is_taxable',0),'included_in_gross'=>(int)$this->input('included_in_gross',0),'included_in_napsa'=>(int)$this->input('included_in_napsa',0),'included_in_nhima'=>(int)$this->input('included_in_nhima',0),'is_recurring'=>(int)$this->input('is_recurring',0),'is_active'=>(int)$this->input('is_active',1)];
    }
}
