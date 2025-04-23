<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Remp\BeamModule\Http\Controllers\DashboardController;
use Remp\BeamModule\Http\Controllers\AccountController;
use Remp\BeamModule\Http\Controllers\SegmentController;
use Remp\BeamModule\Http\Controllers\PropertyController;
use Remp\BeamModule\Http\Controllers\ArticleController;
use Remp\BeamModule\Http\Controllers\AuthController;
use Remp\BeamModule\Http\Controllers\ArticleDetailsController;
use Remp\BeamModule\Http\Controllers\AuthorController;
use Remp\BeamModule\Http\Controllers\AuthorSegmentsController;
use Remp\BeamModule\Http\Controllers\ConversionController;
use Remp\BeamModule\Http\Controllers\SearchController;
use Remp\BeamModule\Http\Controllers\UserPathController;
use Remp\BeamModule\Http\Controllers\NewsletterController;
use Remp\BeamModule\Http\Controllers\SectionController;
use Remp\BeamModule\Http\Controllers\SectionSegmentsController;
use Remp\BeamModule\Http\Controllers\TagCategoryController;
use Remp\BeamModule\Http\Controllers\TagController;
use Remp\BeamModule\Http\Controllers\EntitiesController;
use Remp\BeamModule\Http\Controllers\SettingsController;
use Remp\BeamModule\Http\Middleware\DashboardBasicAuth;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;

Route::get('/error', [AuthController::class, 'error'])->name('sso.error');

// Temporarily use basic auth for public dashboard
// TODO: remove once authentication layer is done
Route::middleware(DashboardBasicAuth::class)->group(function () {
    Route::get('public', [DashboardController::class, 'public'])->name('dashboard.public');
    Route::post('public/articlesJson', [DashboardController::class, 'mostReadArticles'])->name('public.articles.json');
    Route::post('public/timeHistogramJson', [DashboardController::class, 'timeHistogram'])->name('public.timeHistogram.json');
    Route::post('public/timeHistogramNewJson', [DashboardController::class, 'timeHistogramNew'])->name('public.timeHistogramNew.json');
});

// Public route for switching token, available from both public dashboard and authenticated section
Route::post('/properties/switch', [PropertyController::class, 'switch'])->name('properties.switch');

Route::middleware(VerifyJwtToken::class)->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('dashboard/articlesJson', [DashboardController::class, 'mostReadArticles'])->name('dashboard.articles.json');
    Route::post('dashboard/timeHistogramJson', [DashboardController::class, 'timeHistogram'])->name('dashboard.timeHistogram.json');
    Route::post('dashboard/timeHistogramNewJson', [DashboardController::class, 'timeHistogramNew'])->name('dashboard.timeHistogramNew.json');

    Route::get('accounts/json', [AccountController::class, 'json'])->name('accounts.json');
    Route::get('accounts/{account}/properties/json', [PropertyController::class, 'json'])->name('accounts.properties.json');

    Route::get('segments/json', [SegmentController::class, 'json'])->name('segments.json');
    Route::get('segments/{sourceSegment}/copy', [SegmentController::class, 'copy'])->name('segments.copy');
    Route::get('segments/beta/embed', [SegmentController::class, 'embed'])->name('segments.beta.embed');
    Route::get('segments/beta/create', [SegmentController::class, 'betaCreate'])->name('segments.beta.create');
    Route::get('segments/beta/{segment}/edit', [SegmentController::class, 'betaEdit'])->name('segments.beta.edit');

    Route::get('articles/conversions', [ArticleController::class, 'conversions'])->name('articles.conversions');
    Route::get('articles/dtConversions', [ArticleController::class, 'dtConversions'])->name('articles.dtConversions');
    Route::get('articles/pageviews', [ArticleController::class, 'pageviews'])->name('articles.pageviews');
    Route::get('articles/dtPageviews', [ArticleController::class, 'dtPageviews'])->name('articles.dtPageviews');
    Route::post('articles/upsert', [ArticleController::class, 'upsert'])->name('articles.upsert');

    Route::get('conversions/json', [ConversionController::class, 'json'])->name('conversions.json');
    Route::post('conversions/upsert', [ConversionController::class, 'upsert'])->name('conversions.upsert');

    Route::get('author-segments', [AuthorSegmentsController::class, 'index'])->name('authorSegments.index');
    Route::get('author-segments/json', [AuthorSegmentsController::class, 'json'])->name('authorSegments.json');
    Route::get('author-segments/test-parameters', [AuthorSegmentsController::class, 'testingConfiguration'])->name('authorSegments.testingConfiguration');
    Route::post('author-segments/compute', [AuthorSegmentsController::class, 'compute'])->name('authorSegments.compute');
    Route::post('author-segments/validate-test', [AuthorSegmentsController::class, 'validateTest'])->name('authorSegments.validateTest');

    Route::get('section-segments', [SectionSegmentsController::class, 'index'])->name('sectionSegments.index');
    Route::get('section-segments/json', [SectionSegmentsController::class, 'json'])->name('sectionSegments.json');
    Route::get('section-segments/test-parameters', [SectionSegmentsController::class, 'testingConfiguration'])->name('sectionSegments.testingConfiguration');
    Route::post('section-segments/compute', [SectionSegmentsController::class, 'compute'])->name('sectionSegments.compute');
    Route::post('section-segments/validate-test', [SectionSegmentsController::class, 'validateTest'])->name('sectionSegments.validateTest');

    Route::get('authors/dtAuthors', [AuthorController::class, 'dtAuthors'])->name('authors.dtAuthors');
    Route::get('authors/{author}/dtArticles', [AuthorController::class, 'dtArticles'])->name('authors.dtArticles');

    Route::get('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::resource('accounts', AccountController::class);
    Route::resource('accounts.properties', PropertyController::class);

    Route::resource('segments', SegmentController::class);

    Route::get('articles/{article}/histogramJson', [ArticleDetailsController::class, 'timeHistogram'])->name('articles.timeHistogram.json');
    Route::get('articles/{article}/variantsHistogramJson', [ArticleDetailsController::class, 'variantsHistogram'])->name('articles.variantsHistogram.json');
    Route::get('articles/{article}/dtReferers', [ArticleDetailsController::class, 'dtReferers'])->name('articles.dtReferers');

    Route::resource('articles', ArticleController::class, [
        'only' => ['index', 'store'],
    ]);

    Route::resource('articles', ArticleDetailsController::class, [
        'only' => ['show'],
    ]);
    Route::get('article/{article?}', [ArticleDetailsController::class, 'showByParameter']);

    Route::get('newsletters/json', [NewsletterController::class, 'json'])->name('newsletters.json');
    Route::post('newsletters/validate', [NewsletterController::class, 'validateForm'])->name('newsletters.validateForm');
    Route::post('newsletters/{newsletter}/start', [NewsletterController::class, 'start'])->name('newsletters.start');
    Route::post('newsletters/{newsletter}/pause', [NewsletterController::class, 'pause'])->name('newsletters.pause');
    Route::resource('newsletters', NewsletterController::class, ['except' => ['show']]);

    Route::get('userpath', [UserPathController::class, 'index'])->name('userpath.index');
    Route::post('userpath/statsJson', [UserPathController::class, 'stats'])->name('userpath.stats');
    Route::post('userpath/diagram', [UserPathController::class, 'diagramData'])->name('userpath.diagramData');

    Route::resource('conversions', ConversionController::class, [
        'only' => ['index', 'store', 'show']
    ]);
    Route::resource('authors', AuthorController::class, [
        'only' => ['index', 'show']
    ]);

    Route::get('sections/dtSections', [SectionController::class, 'dtSections'])->name('sections.dtSections');
    Route::get('sections/{section}/dtArticles', [SectionController::class, 'dtArticles'])->name('sections.dtArticles');
    Route::resource('sections', SectionController::class, [
        'only' => ['index', 'show']
    ]);

    Route::get('tags/dtTags', [TagController::class, 'dtTags'])->name('tags.dtTags');
    Route::get('tags/{tag}/dtArticles', [TagController::class, 'dtArticles'])->name('tags.dtArticles');
    Route::resource('tags', TagController::class, [
        'only' => ['index', 'show']
    ]);

    Route::get('tag-categories/dtTagCategories', [TagCategoryController::class, 'dtTagCategories'])->name('tagCategories.dtTagCategories');
    Route::get('tag-categories/{tagCategory}/dtTags', [TagCategoryController::class, 'dtTags'])->name('tagCategories.dtTags');
    Route::get('tag-categories/{tagCategory}/dtArticles', [TagCategoryController::class, 'dtArticles'])->name('tagCategories.dtArticles');
    Route::resource('tag-categories', TagCategoryController::class, [
        'only' => ['index', 'show']
    ]);

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/{configCategory}', [SettingsController::class, 'update'])->name('settings.update');

    Route::post('entities/validate/{entity?}', [EntitiesController::class, 'validateForm'])->name('entities.validateForm');
    Route::get('entities/json', [EntitiesController::class, 'json'])->name('entities.json');
    Route::resource('entities', EntitiesController::class);

    Route::get('search', [SearchController::class, 'search'])->name('search');
});
