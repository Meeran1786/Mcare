<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactQuery;

class ManageContactQueryController extends Controller
{
    public function index()
    {
        $queries = ContactQuery::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.contacts.index', compact('queries'));
    }

    public function markRead($id)
    {
        $query = ContactQuery::findOrFail($id);
        $query->is_read = true;
        $query->save();

        return redirect()->back()->with('success', 'Contact query marked as read successfully.');
    }

    public function destroy($id)
    {
        $query = ContactQuery::findOrFail($id);
        $query->delete();

        return redirect()->back()->with('success', 'Contact query deleted successfully.');
    }

    public function destroySelected(Request $request)
    {
        $data = $request->validate([
            'query_ids' => 'required|array|min:1',
            'query_ids.*' => 'integer|exists:contact_queries,id',
        ]);

        ContactQuery::whereIn('id', $data['query_ids'])->delete();

        return redirect()->back()->with('success', 'Selected contact queries deleted successfully.');
    }

    public function destroyAll()
    {
        ContactQuery::query()->delete();

        return redirect()->back()->with('success', 'All contact queries deleted successfully.');
    }
}
