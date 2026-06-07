<?php

namespace App\Filament\Store\Resources\ForumThreads\Pages;

use App\Filament\Store\Resources\ForumThreads\ForumThreadResource;
use Filament\Resources\Pages\ListRecords;

class ListForumThreads extends ListRecords
{
    protected static string $resource = ForumThreadResource::class;
}
