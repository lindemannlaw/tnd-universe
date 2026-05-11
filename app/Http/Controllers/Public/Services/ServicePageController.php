<?php

namespace App\Http\Controllers\Public\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Service;
use App\Models\SiteSection;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ServicePageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(Service $service): Response|View
    {
        $page = $service;
        $info = [
            [
                'title' => 'Tax structuring',
                'description' => 'Investments, holdings, managed accounts, securitisations and investment funds; may your investors be (highly) taxed or tax exempt, retail or institutional, we help you to find and set up your tailormade solution',
            ],
            [
                'title' => 'Tax',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. At eveniet, facilis harum labore nam nihil nulla praesentium recusandae repellendus. Commodi earum error est qui similique ut vitae voluptates. Dignissimos, sint.',
            ],
            [
                'title' => 'Swiss & international fund tax reporting',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis consectetur consequuntur dolor eos omnis similique sint vel voluptates voluptatibus. Accusantium atque cumque doloribus eligendi, et exercitationem fugiat magni minus nesciunt non obcaecati possimus quia quod reiciendis repellat rerum sit temporibus?',
            ],
            [
                'title' => 'Swiss taxes',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus aliquam aspernatur commodi debitis esse et ex illum in ipsum minus mollitia, neque placeat possimus provident quibusdam, tempore vel, voluptatibus? A accusamus asperiores aut autem dicta numquam ullam unde veniam. Consequuntur dicta ea enim et facere labore laboriosam natus nihil odio quaerat quas quibusdam quo recusandae, repellat sapiente sunt ullam vel vitae! Accusantium aliquam architecto eligendi, enim error hic impedit magni minima necessitatibus, nihil optio placeat quo recusandae repudiandae sequi vero!',
            ],
            [
                'title' => 'Transparency',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Animi aspernatur facere nemo perferendis sed soluta. Facere, quas, sequi. Ad animi blanditiis cupiditate eius iste nam odit quo quos repellendus, tenetur unde, vero. Aut autem doloremque eligendi est excepturi facere hic id illo, impedit ipsa ipsum modi, nobis officia placeat porro quaerat rem sapiente voluptatem! Alias dicta libero soluta totam voluptatem.',
            ],
            [
                'title' => 'International Tax Coordination',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis consectetur consequuntur dolor eos omnis similique sint vel voluptates voluptatibus. Accusantium atque cumque doloribus eligendi, et exercitationem fugiat magni minus nesciunt non obcaecati possimus quia quod reiciendis repellat rerum sit temporibus?',
            ],
            [
                'title' => 'Conflicts',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. At eveniet, facilis harum labore nam nihil nulla praesentium recusandae repellendus. Commodi earum error est qui similique ut vitae voluptates. Dignissimos, sint.',
            ],
        ];
        $contactUsSection = SiteSection::where('slug', 'contact-us')->first();

        return $this->seoResponse(
            'public.pages.services.service',
            compact('page', 'service', 'info', 'contactUsSection'),
            $service->updated_at,
        );
    }
}
