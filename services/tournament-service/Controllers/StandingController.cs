using System;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Scoreboard.Infrastructure;
using Scoreboard.Services.External;

namespace Scoreboard.Controllers;

[ApiController]
[Route("api/standings")]
public class StandingsController(AppDbContext db, ITeamDirectory teams) : ControllerBase
{
    private readonly ITeamDirectory _teams = teams;

    [HttpGet]
    public async Task<IActionResult> GetStandings()
    {
        var wins = await db.TeamWins
            .GroupBy(tw => tw.TeamId)
            .Select(g => new { TeamId = g.Key, Wins = g.Count() })
            .ToListAsync();

        var winsLookup = wins.ToDictionary(x => x.TeamId, x => x.Wins);

        var matchTeams = await db.Matches
            .Select(m => new { m.HomeTeamId, m.AwayTeamId })
            .ToListAsync();

        var teamIds = matchTeams
            .SelectMany(m => new[] { m.HomeTeamId, m.AwayTeamId })
            .Distinct()
            .ToList();

        if (teamIds.Count == 0)
            return Ok(Array.Empty<object>());

        var summaries = await _teams.GetTeamsAsync(teamIds);

        var rows = teamIds
            .Select(id =>
            {
                var summary = summaries.TryGetValue(id, out var team)
                    ? team
                    : new TeamSummary(id, $"Equipo #{id}", null);
                var winsValue = winsLookup.TryGetValue(id, out var w) ? w : 0;
                return new
                {
                    id = summary.Id,
                    name = summary.Name,
                    color = summary.Color,
                    wins = winsValue
                };
            })
            .OrderByDescending(x => x.wins)
            .ThenBy(x => x.name)
            .ToList();

        return Ok(rows);
    }
}
