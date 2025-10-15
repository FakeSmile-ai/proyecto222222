using System;
using System.Net.Http.Json;
using System.Collections.Generic;
using System.Linq;
using Microsoft.Extensions.Options;

namespace Scoreboard.Services.External;

public record TeamDirectoryOptions
{
    public string BaseUrl { get; set; } = "http://team-service:8081";
}

public record TeamSummary(int Id, string Name, string? Color);

public interface ITeamDirectory
{
    Task<TeamSummary?> GetTeamAsync(int id, CancellationToken cancellationToken = default);
    Task<IDictionary<int, TeamSummary>> GetTeamsAsync(IEnumerable<int> ids, CancellationToken cancellationToken = default);
    Task<int?> CreateTeamAsync(string name, string? color, CancellationToken cancellationToken = default);
}

public class TeamDirectoryClient : ITeamDirectory
{
    private readonly HttpClient _httpClient;

    public TeamDirectoryClient(HttpClient httpClient, IOptions<TeamDirectoryOptions> options)
    {
        _httpClient = httpClient;
        var baseUrl = options.Value.BaseUrl?.TrimEnd('/') ?? "http://team-service:8081";
        _httpClient.BaseAddress = new Uri(baseUrl);
    }

    public async Task<TeamSummary?> GetTeamAsync(int id, CancellationToken cancellationToken = default)
    {
        try
        {
            var response = await _httpClient.GetAsync($"/teams/{id}", cancellationToken);
            if (!response.IsSuccessStatusCode) return null;

            var payload = await response.Content.ReadFromJsonAsync<TeamResponseDto>(cancellationToken: cancellationToken);
            return payload is null ? null : new TeamSummary(payload.id, payload.name, payload.color);
        }
        catch
        {
            return null;
        }
    }

    public async Task<IDictionary<int, TeamSummary>> GetTeamsAsync(IEnumerable<int> ids, CancellationToken cancellationToken = default)
    {
        var results = new Dictionary<int, TeamSummary>();
        foreach (var id in ids.Distinct())
        {
            var team = await GetTeamAsync(id, cancellationToken);
            if (team is not null)
            {
                results[id] = team;
            }
        }

        return results;
    }

    public async Task<int?> CreateTeamAsync(string name, string? color, CancellationToken cancellationToken = default)
    {
        var payload = new
        {
            name,
            color,
            players = Array.Empty<object>()
        };

        try
        {
            var response = await _httpClient.PostAsJsonAsync("/teams", payload, cancellationToken);
            if (!response.IsSuccessStatusCode) return null;

            var body = await response.Content.ReadFromJsonAsync<TeamResponseDto>(cancellationToken: cancellationToken);
            return body?.id;
        }
        catch
        {
            return null;
        }
    }

    private record TeamResponseDto(int id, string name, string? color);
}
