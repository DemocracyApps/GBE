<?php namespace DemocracyApps\GB\Http\Controllers\Government;

use Aws\CloudFront\Exception\Exception;
use DemocracyApps\GB\Http\Controllers\Controller;
use DemocracyApps\GB\Data\DataUtilities;
use DemocracyApps\GB\Utility\CurlUtilities;

use DemocracyApps\GB\Jobs\ProcessUpload;
use DemocracyApps\GB\Organizations\GovernmentOrganization;
use Illuminate\Http\Request;

class GovernmentDataController extends Controller {

  protected $governmentOrganization = null;


  protected $dataSource = null;

    public function __construct(GovernmentOrganization $org)
    {
      $this->governmentOrganization = $org;
    }


    public function index($govt_org_id, Request $request)
    {
      // Query the data server for any datasets associated with this entity.
      $url = DataUtilities::getDataserverEndpoint($govt_org_id) . '/api/v1/get_entity_info?entity_id='.$govt_org_id;
      $params = [];
      $retry = true;
      $timeout = 10;
      $attempts = 2;
      $returnValue = CurlUtilities::curlJsonGet($url, $timeout, $attempts);
      $error = false;
      $errorMessage = "No response from data server.";

      if (!isset($returnValue) || $returnValue == "") {
            $error = true;
      }
      else {
        $returnValue = json_decode($returnValue, true);
        if (!is_array($returnValue)) {
          $error = true;
          $errorMessage = "Unknown error requesting data.";
        }
        else {
          if (array_key_exists("error", $returnValue)) {
            $error = true;
            $errorMessage = $returnValue['message'];
          }
        }
      }

      $datasets = [];
      $dataSources = [];
      if (!$error) {
        $datasets = $returnValue['data']['datasets'];
        $dataSources = $returnValue['data']['datasources'];
      }
      $organization = GovernmentOrganization::find($govt_org_id);
      return view('government.data.index', array('organization'=>$organization,
        'dataSources' => $dataSources, 'datasets' => $datasets, 'dataError' => $error,
        'dataErrorMessage' => $errorMessage));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create($govt_org_id, Request $request)
    {
      $organization = GovernmentOrganization::find($govt_org_id);
      $sourceId = $request->get('datasource');
      return view('government.data.create', array('organization'=>$organization, 'datasource' => $sourceId));
    }

    public function store($govt_org_id, Request $request)
    {
        $rules = null;
        if ($request->has('type') && $request->get('type') == 'api') {
            $rules = ['name' => 'required', 'type' => 'in:file,api', 'endpoint' => 'required | url'];
        }
        else {
            $rules = ['name' => 'required', 'type' => 'in:file,api'];
        }
        $this->validate($request, $rules);

        $parameters = array();
        $organization = GovernmentOrganization::find($govt_org_id);
        $parameters['name'] = $request->get('name');
        $parameters['sourceType'] = $request->get('type');
        if ($request->has('description')) $parameters['description'] = $request->get('description');
        $parameters['entity'] = $organization->name;
        $parameters['entityId'] = $organization->id;
        if ($parameters['sourceType'] == 'api') {
            $parameters['endpoint'] = $request->get('endpoint');
            $parameters['apiFormat'] = $request->get('api-format');
            $parameters['dataFormat'] = $request->get('data-format');
            $parameters['frequency'] = $request->get('frequency');
        }
        else if ($parameters['sourceType'] == 'file') {
            $parameters['dataFormat'] = $request->get('data-format');
        }

        $url = DataUtilities::getDataserverEndpoint($organization->id) . '/api/v1/register_data_source';

        $returnValue = CurlUtilities::curlJsonPost($url, json_encode($parameters));

        return redirect("/governments/$organization->id/data");
    }

    public function upload($govt_org_id, Request $request)
    {
        if ($request->method() == 'GET') {
          $organization = GovernmentOrganization::find($govt_org_id);
          $dataSourceId = $request->get('datasource');
          $url = DataUtilities::getDataserverEndpoint($govt_org_id) . '/api/v1/datasources/'.$dataSourceId;
          $params = [];
          $timeout = 10;
          $attempts = 2;
          $returnValue = CurlUtilities::curlJsonGet($url, $timeout, $attempts);
          $error = false;
          $errorMessage = "No response from data server.";

          if (!isset($returnValue)) {
                $error = true;
          }
          else {
            $returnValue = json_decode($returnValue, true);
            if (!is_array($returnValue)) {
              $error = true;
              $errorMessage = "Unknown error requesting data.";
            }
            else {
              if (array_key_exists("error", $returnValue)) {
                $error = true;
                $errorMessage = $returnValue['message'];
              }
            }
          }
          if ($error) {
              return view('government.data.error_upload', array('organization'=>$organization, 'message'=>$errorMessage));
          }
          else {
              return view('government.data.upload', array('organization' => $organization, 'datasource' => $dataSourceId,
                                                          'format'=>$returnValue['data']['dataFormat']));
          }
        }
        else { // POST

            $format = $request->get('format');
            if ($format == 'simple-budget') {
              $rules = ['year' => 'required | digits:4', 'year_count'=>'required | integer',
                'categories' => 'required | integer'];
              $this->validate($request, $rules);

              if (! $request->hasFile('data')) {
                  return redirect()->back()->withInput()->withErrors(array('file'=>'You must select a file to upload'));
              }
            }
            else if ($format == 'simple-project') {

            }
            else {
              throw new \Exception("Unknown format $format in data upload");
            }
            $organization = GovernmentOrganization::find($govt_org_id);
            $datasourceId = $request->get('datasource');
            $parameters = new \stdClass();
            $parameters->organization = $organization->name;
            $parameters->organization_id = $govt_org_id;
            $parameters->datasource_id = $request->get('datasource');
            $parameters->format = $format;
            if ($parameters->format == 'simple-budget') {
                $parameters->type = $request->get('type');
                $parameters->year_count = $request->get('year_count');
                $parameters->start_year = $request->get('year');
                $parameters->category_count = $request->get('categories');
            }
            else if ($parameters->format = 'simple-project') {

            }

            $file = $request->file('data');
            $name = uniqid('upload');
            $file->move('/var/www/cbe/public/downloads', $name);
            $parameters->file_path = '/var/www/cbe/public/downloads/' . $name;

            $job = new ProcessUpload($parameters);
            $this->dispatch($job);
            return redirect("/governments/$govt_org_id/data");
        }
    }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function edit($govt_org_id, $id)
  {
//        $organization = GovernmentOrganization::find($govt_org_id);
//        $orgUser = GovernmentOrganizationUser::find($id);
//        $user = User::find($orgUser->user_id);
//        return view('government.users.edit', array('organization'=>$organization, 'orgUser'=>$orgUser, 'user'=>$user));
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function update($govt_org_id, $id, Request $request)
  {
//        $rules = ['access'=>'required'];
//        $this->validate($request, $rules);
//        $orgUser = GovernmentOrganizationUser::find($id);
//        $orgUser->access = $request->get('access');
//        $orgUser->save();
//
//        return redirect('/governments/'.$govt_org_id.'/users');

  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {
    //
  }

}