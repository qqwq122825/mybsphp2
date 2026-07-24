<?php

namespace App\Http\Controllers;

use App\Cards\CardCredentialHasher;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicCardQueryController extends Controller
{
    public function show(): View
    {
        return view('public.activation-query', [
            'credential' => '',
            'card' => null,
            'queried' => false,
        ]);
    }

    public function query(Request $request, CardCredentialHasher $hasher): View
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'credential.required' => '请输入激活码或充值卡编码。',
            'credential.min' => '编码长度至少为 6 位。',
        ]);

        $credential = trim((string) $validated['credential']);
        $card = Card::query()
            ->with(['application', 'cardType'])
            ->where('code_lookup_hash', $hasher->lookupHash($credential))
            ->first();

        return view('public.activation-query', [
            'credential' => $credential,
            'card' => $card,
            'queried' => true,
        ]);
    }
}
