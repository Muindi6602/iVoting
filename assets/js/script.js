$(document).ready(function() {
    // Load initial results
    loadResults();
    
    // Set interval to refresh results every 5 seconds
    setInterval(loadResults, 5000);
    
    // Handle vote submission
    $('.vote-form').on('submit', function(e) {
        e.preventDefault();
        const electionId = $(this).data('election-id');
        const formData = $(this).serializeArray();
        
        // Group votes by position
        const votes = {};
        formData.forEach(item => {
            const positionId = item.name.replace('position_', '');
            votes[positionId] = item.value;
        });
        
        Swal.fire({
            title: 'Confirm Your Vote',
            text: 'Are you sure you want to submit your vote? This action cannot be undone.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, submit my vote!'
        }).then((result) => {
            if (result.isConfirmed) {
                submitVote(electionId, votes);
            }
        });
    });
    
    function submitVote(electionId, votes) {
        $.ajax({
            url: 'api/vote.php',
            method: 'POST',
            dataType: 'json',
            data: {
                election_id: electionId,
                votes: votes
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your vote has been submitted successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to submit vote. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while submitting your vote. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }
    
    function loadResults() {
        $('[id^="electionResults-"]').each(function() {
            const electionId = this.id.replace('electionResults-', '');
            $.ajax({
                url: 'api/results.php',
                method: 'GET',
                data: { election_id: electionId },
                success: function(response) {
                    if (response.success) {
                        $(`#electionResults-${electionId}`).html(renderResults(response.data));
                        renderCharts(response.data);
                    }
                }
            });
        });
    }
    
    function renderResults(data) {
        let html = '';
        
        data.forEach(positionData => {
            html += `<div class="position-results mb-4" data-position-id="${positionData.position.id}">`;
            html += `<h6>${positionData.position.title}</h6>`;
            html += `<div class="table-responsive">`;
            html += `<table class="table table-sm">`;
            html += `<thead><tr><th>Candidate</th><th>Votes</th><th>Percentage</th></tr></thead>`;
            html += `<tbody>`;
            
            positionData.candidates.forEach(candidate => {
                html += `<tr>`;
                html += `<td>${candidate.name}</td>`;
                html += `<td>${candidate.vote_count}</td>`;
                html += `<td>${candidate.percentage}%</td>`;
                html += `</tr>`;
            });
            
            html += `</tbody>`;
            html += `</table>`;
            html += `</div>`;
            html += `<div class="chart-container" style="position: relative; height:200px;">`;
            html += `<canvas id="chart-${positionData.position.id}"></canvas>`;
            html += `</div>`;
            html += `</div>`;
        });
        
        return html;
    }
    
    function renderCharts(data) {
        data.forEach(positionData => {
            const ctx = document.getElementById(`chart-${positionData.position.id}`).getContext('2d');
            const labels = positionData.candidates.map(c => c.name);
            const votes = positionData.candidates.map(c => c.vote_count);
            const backgroundColors = positionData.candidates.map((c, i) => {
                return i === 0 && positionData.total_votes > 0 ? 
                    'rgba(54, 162, 235, 0.7)' : // Blue for leading candidate
                    `hsla(${(i * 360) / positionData.candidates.length}, 70%, 50%, 0.7)`;
            });
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors.map(c => c.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    }
});