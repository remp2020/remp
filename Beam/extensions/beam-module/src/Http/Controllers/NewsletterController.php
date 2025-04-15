<?php

namespace Remp\BeamModule\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Remp\BeamModule\Contracts\Mailer\MailerContract;
use Remp\BeamModule\Http\Requests\NewsletterRequest;
use Remp\BeamModule\Http\Resources\NewsletterResource;
use Remp\BeamModule\Model\Newsletter;
use Remp\LaravelHelpers\Resources\JsonResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Yajra\DataTables\DataTables;

class NewsletterController extends Controller
{
    private $mailer;

    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'segment', 'state', 'mailer_generator_id', 'created_at', 'updated_at', 'starts_at'];
        $newsletters = Newsletter::select($columns);

        return $datatables->of($newsletters)
            ->addColumn('newsletter', function (Newsletter $newsletter) {
                return [
                    'url' => route('newsletters.edit', ['newsletter' => $newsletter]),
                    'text' => $newsletter->name,
                ];
            })
            ->addColumn('action_methods', function (Newsletter $newsletter) {
                return [
                    'start' => 'POST',
                    'pause' => 'POST',
                    'destroy' => 'DELETE',
                ];
            })
            ->addColumn('actions', function (Newsletter $n) {
                return [
                    'edit' => route('newsletters.edit', $n),
                    'start' => $n->isPaused() ? route('newsletters.start', $n) : null,
                    'pause' => $n->isStarted() ? route('newsletters.pause', $n) : null,
                    'destroy' => route('newsletters.destroy', $n),
                ];
            })
            ->rawColumns(['actions', 'action_methods', 'newsletter'])
            ->make(true);
    }

    public function validateForm(NewsletterRequest $request)
    {
        return response()->json(false);
    }

    public function index()
    {
        return response()->format([
            'html' => view('beam::newsletters.index'),
            'json' => NewsletterResource::collection(Newsletter::paginate()),
        ]);
    }

    public function create()
    {
        $segments = $this->loadSegments();
        $generators = $this->loadGenerators();
        $criteria = $this->loadCriteria();
        $mailTypes = $this->loadMailTypes();
        $newsletter = new Newsletter();
        $newsletter->starts_at = Carbon::now()->addHour();
        $newsletter->articles_count = 1;
        $newsletter->personalized_content = false;

        if ($generators->isEmpty()) {
            flash('No source templates using best_performing_articles generator were configured on Mailer', 'danger');
        }
        if ($mailTypes->isEmpty()) {
            flash('No mail types are available on Mailer, please configure them first', 'danger');
        }
        if (empty($segments)) {
            flash('No segments are available on Mailer, please configure them first', 'danger');
        }

        return response()->format([
            'html' => view(
                'beam::newsletters.create',
                compact(['newsletter', 'segments', 'generators', 'criteria', 'mailTypes'])
            ),
            'json' => [],
        ]);
    }

    public function edit($id)
    {
        $newsletter = Newsletter::find($id);
        $segments = $this->loadSegments();
        $generators = $this->loadGenerators();
        $criteria = $this->loadCriteria();
        $mailTypes = $this->loadMailTypes();

        return response()->format([
            'html' => view(
                'beam::newsletters.edit',
                compact(['newsletter', 'segments', 'generators', 'criteria', 'mailTypes'])
            ),
            'json' => [],
        ]);
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return response()->format([
            'html' => redirect(route('newsletters.index'))->with('success', sprintf(
                "Newsletter [%s] was removed",
                $newsletter->name
            )),
            'json' => new NewsletterResource([]),
        ]);
    }

    private function loadCriteria()
    {
        return Newsletter\NewsletterCriterionEnum::getFriendlyList();
    }

    private function loadGenerators()
    {
        return $this->mailer->generatorTemplates('best_performing_articles')
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->title];
            });
    }

    private function loadSegments()
    {
        return $this->mailer->segments()->mapToGroups(function ($item) {
            return [$item->provider => [$item->provider . '::' . $item->code => $item->name]];
        })->mapWithKeys(function ($item, $key) {
            return [$key => $item->collapse()];
        })->toArray();
    }

    private function loadMailTypes()
    {
        return $this->mailer->mailTypes()
            ->mapWithKeys(function ($item) {
                return [$item->code => $item->title];
            });
    }

    public function store(NewsletterRequest $request)
    {
        $newsletter = new Newsletter();
        $newsletter->fill($request->all());
        $newsletter->state = Newsletter::STATE_PAUSED;
        $newsletter->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'newsletters.index',
                    self::FORM_ACTION_SAVE => 'newsletters.edit',
                ],
                $newsletter
            )->with('success', sprintf('Newsletter [%s] was created', $newsletter->name)),
            'json' => new NewsletterResource($newsletter)
        ]);
    }

    public function update(NewsletterRequest $request, Newsletter $newsletter)
    {
        $newsletter->fill($request->all());
        $newsletter->personalized_content = $request->input('personalized_content', false);
        $newsletter->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'newsletters.index',
                    self::FORM_ACTION_SAVE => 'newsletters.edit',
                ],
                $newsletter
            )->with('success', sprintf('Newsletter [%s] was updated', $newsletter->name)),
            'json' => new NewsletterResource($newsletter)
        ]);
    }

    public function start(Newsletter $newsletter)
    {
        if ($newsletter->isFinished()) {
            return response()->format([
                'html' => redirect(route('newsletters.index'))->with('success', sprintf(
                    'Newsletter [%s] was already finished, cannot start it again.',
                    $newsletter->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot start already finished newsletter')),
            ]);
        }

        $newsletter->state = Newsletter::STATE_STARTED;
        $newsletter->save();

        return response()->format([
            'html' => redirect(route('newsletters.index'))->with('success', sprintf(
                'Newsletter [%s] was started manually',
                $newsletter->name
            )),
            'json' => new NewsletterResource([]),
        ]);
    }

    public function pause(Newsletter $newsletter)
    {
        if ($newsletter->isFinished()) {
            return response()->format([
                'html' => redirect(route('newsletters.index'))->with('success', sprintf(
                    'Newsletter [%s] was already finished, cannot be paused.',
                    $newsletter->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot pause already finished newsletter')),
            ]);
        }

        $newsletter->state = Newsletter::STATE_PAUSED;
        $newsletter->save();

        return response()->format([
            'html' => redirect(route('newsletters.index'))->with('success', sprintf(
                'Newsletter [%s] was paused manually',
                $newsletter->name
            )),
            'json' => new NewsletterResource([]),
        ]);
    }
}
