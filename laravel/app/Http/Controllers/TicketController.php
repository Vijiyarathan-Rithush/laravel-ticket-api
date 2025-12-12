<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('user')
            ->where('user_id', $request->user()->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority'    => ['nullable', 'in:low,medium,high'],
        ]);

        $ticket = Ticket::create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'priority'    => $validated['priority'] ?? 'medium',
            'status'      => 'open',
            'user_id'     => $request->user()->id,
        ]);

        return response()->json($ticket, 201);
    }

    public function show(Request $request, $id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        return $ticket;
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $validated = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status'      => ['sometimes', 'in:open,in_progress,closed'],
            'priority'    => ['sometimes', 'in:low,medium,high'],
        ]);

        $ticket->update($validated);

        return $ticket;
    }

    public function destroy(Request $request, $id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted.']);
    }
}