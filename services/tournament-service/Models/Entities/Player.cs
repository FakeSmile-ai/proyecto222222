using System;

namespace Scoreboard.Models.Entities;

public class Player
{
    public int Id { get; set; }
    public string Name { get; set; } = string.Empty;
    public int? Number { get; set; }
    public int TeamId { get; set; }

    public Team Team { get; set; } = null!;
}
