<?php

namespace UksusoFF\WebtreesModules\Photos\Modules;

use Fisharebest\Webtrees\Contracts\TimestampInterface;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleBlockTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UksusoFF\WebtreesModules\Photos\Helpers\DatabaseHelper;

class PhotosModule extends AbstractModule implements ModuleCustomInterface, ModuleBlockInterface
{
    use ModuleCustomTrait;
    use ModuleBlockTrait;

    public const CUSTOM_VERSION = '0.0.1';

    public const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-photos';

    private const MAX_ITEMS = 30;

    public DatabaseHelper $query;

    public function __construct()
    {
        $this->query = new DatabaseHelper();
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    public function title(): string
    {
        return I18N::translate('Recent photos');
    }

    public function description(): string
    {
        return I18N::translate('A list of photos that have been uploaded recently.');
    }

    public function customModuleAuthorName(): string
    {
        return 'UksusoFF';
    }

    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }

    public function customTranslations(string $language): array
    {
        $file = $this->resourcesFolder() . "langs/{$language}.php";

        return file_exists($file)
            ? require $file
            : require $this->resourcesFolder() . 'langs/en.php';
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . '/../../resources/';
    }

    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
        $rows = $this->getData($tree, self::MAX_ITEMS);

        $content = $rows->isEmpty()
            ? I18N::translate('There have been no photos found.')
            : view("{$this->name()}::list", [
                'id' => $block_id,
                'rows' => $rows,
            ]);

        if ($context !== self::CONTEXT_EMBED) {
            return view('modules/block-template', [
                'block' => Str::kebab($this->name()),
                'id' => $block_id,
                'config_url' => $this->configUrl($tree, $context, $block_id),
                'title' => I18N::plural('Last %s uploaded photos', 'Last %s uploaded photos', self::MAX_ITEMS, I18N::number(self::MAX_ITEMS)),
                'content' => $content,
            ]);
        }

        return $content;
    }

    public function loadAjax(): bool
    {
        return true;
    }

    public function isUserBlock(): bool
    {
        return true;
    }

    public function isTreeBlock(): bool
    {
        return true;
    }

    protected function getData(Tree $tree, int $limit): Collection
    {
        return $this->query
            ->getRecentPhotos($tree, $limit)
            ->map(function(object $row) use ($tree): object {
                $media = Registry::mediaFactory()->make($row->m_id, $tree);

                return (object)[
                    'media' => $media,
                    'changed' => Registry::timestampFactory()->fromString($row->change_time),
                ];
            })
            ->filter(static function(object $row): bool {
                return $row->media instanceof Media && $row->media->canShow() && $row->media->firstImageFile() !== null;
            })
            ->groupBy(static function(object $row): string {
                assert($row->changed instanceof TimestampInterface);

                return $row->changed->format('Y-m-d');
            });
    }
}
